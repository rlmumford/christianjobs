<?php

namespace Drupal\job_board;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\SynchronizableEntityTrait;
use Drupal\Core\Entity\SynchronizableInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\job_role\Entity\JobRole;

class JobBoardJobRole extends JobRole implements PurchasableEntityInterface {
  use SynchronizableEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if ($this->paid->value && !$this->end_date->value) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = clone $this->publish_date->date;
      $end_date->add(new \DateInterval($this->initial_duration->value ?: 'P30D'));
      $this->end_date->value = $end_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }

    if ($this->application_deadline->value && ($this->application_deadline->value < $this->end_date->value)) {
      $this->end_date->value = $this->application_deadline->value;
    }

    $this->geocodeLocation();
  }

  /**
   * Geocode the location.
   */
  protected function geocodeLocation() {
    /** @var \Drupal\geocoder_field\PreprocessorPluginManager $preprocessor_manager */
    $preprocessor_manager = \Drupal::service('plugin.manager.geocoder.preprocessor');
    /** @var \Drupal\geocoder\DumperPluginManager $dumper_manager */
    $dumper_manager = \Drupal::service('plugin.manager.geocoder.dumper');

    $address = $this->location;
    if ($this->original) {
      $original_address = $this->original->location;
    }

    // First we need to Pre-process field.
    // Note: in case of Address module integration this creates the
    // value as formatted address.
    $preprocessor_manager->preprocess($address);

    // Skip any action if:
    // geofield has value and remote field value has not changed.
    if (isset($original_address) && !$this->get('location_geo')->isEmpty() && $address->getValue() == $original_address->getValue()) {
      return;
    }

    $dumper = $dumper_manager->createInstance('geojson');
    $result = [];

    foreach ($address->getValue() as $delta => $value) {
      if ($address->getFieldDefinition()->getType() == 'address_country') {
        $value['value'] = CountryManager::getStandardList()[$value['value']];
      }

      $address_collection = isset($value['value']) ? \Drupal::service('geocoder')->geocode($value['value'], ['googlemaps', 'googlemaps_business']) : NULL;
      if ($address_collection) {
        $result[$delta] = $dumper->dump($address_collection->first());

        // We can't use DumperPluginManager::fixDumperFieldIncompatibility
        // because we do not have a FieldConfigInterface.
        // Fix not UTF-8 encoded result strings.
        // https://stackoverflow.com/questions/6723562/how-to-detect-malformed-utf-8-string-in-php
        if (is_string($result[$delta])) {
          if (!preg_match('//u', $result[$delta])) {
            $result[$delta] = utf8_encode($result[$delta]);
          }
        }
      }
    }

    $this->set('location_geo', $result);
  }

  /**
   * Gets the stores through which the purchasable entity is sold.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface[]
   *   The stores.
   */
  public function getStores() {
    return $this->entityTypeManager()->getStorage('commerce_store')->loadMultiple();
  }

  /**
   * Gets the purchasable entity's order item type ID.
   *
   * Used for finding/creating the appropriate order item when purchasing a
   * product (adding it to an order).
   *
   * @return string
   *   The order item type ID.
   */
  public function getOrderItemTypeId() {
    return 'job_board_job_role';
  }

  /**
   * Gets the purchasable entity's order item title.
   *
   * Saved in the $order_item->title field to protect the order items of
   * completed orders against changes in the referenced purchased entity.
   *
   * @return string
   *   The order item title.
   */
  public function getOrderItemTitle() {
    $title = $this->label()." (".($this->initial_duration->value == 'P60D' ? '60 Days' : '30 Days').")";

    if ($this->isRPO()) {
      $title .= " [RPO]";
    }

    return $title;
  }

  /**
   * Gets the purchasable entity's price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The price, or NULL.
   */
  public function getPrice(Context $context = NULL) {
    if ($this->rpo->value) {
      $price = new Price('895.00', 'GBP');
    }
    else if ($this->initial_duration->value == 'P60D') {
      $price = new Price('100.00', 'GBP');
    }
    else {
      $price = new Price('75.00', 'GBP');
    }

    return $price;
  }

  /**
   * Check whether the job is published.
   *
   * @return boolean
   */
  public function isActive() {
    if ($this->end_date->isEmpty() || $this->publish_date->isEmpty()) {
      return FALSE;
    }

    $start_date = $this->publish_date->date->format('Y-m-d');
    $end_date = $this->end_date->date->format('Y-m-d');
    $current_date = (new DrupalDateTime())->format('Y-m-d');

    return ($current_date >= $start_date) && ($current_date <= $end_date);
  }

  /**
   * Set whether the job is active.
   */
  public function setActive($active) {}

  /**
   * Is this job post an RPO.
   *
   * @return boolean
   */
  public function isRPO() {
    return $this->rpo->value ? TRUE : FALSE;
  }

}
