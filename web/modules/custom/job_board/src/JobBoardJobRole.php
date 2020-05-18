<?php

namespace Drupal\job_board;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\SynchronizableEntityTrait;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\job_role\Entity\JobRole;
use Drupal\organization_user\Entity\EntityOwnerOrganizationTrait;
use Drupal\user\EntityOwnerInterface;

class JobBoardJobRole extends JobRole implements PurchasableEntityInterface {
  use SynchronizableEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if ($this->paid->value && !$this->paid_to_date->value) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $paid_to_date */
      $paid_to_date = clone $this->publish_date->date;
      $paid_to_date->add(new \DateInterval($this->initial_duration->value ?: 'P30D'));
      $this->paid_to_date->value = $paid_to_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }

    $this->end_date->value = $this->paid_to_date->value;
    if ($this->application_deadline->value && ($this->application_deadline->value < $this->end_date->value)) {
      $this->end_date->value = $this->application_deadline->value;
    }
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
    $config = \Drupal::config('job_board.pricing');

    if ($this->rpo->value) {
      $price = new Price($config->get('job_RPO'), 'GBP');
    }
    else if ($this->initial_duration->value == 'P60D') {
      $price = new Price($config->get('job_60D'), 'GBP');
    }
    else {
      $price = new Price($config->get('job_30D'), 'GBP');
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

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   */
  public static function getDefaultEntityOrganization(EntityInterface $entity, FieldDefinitionInterface $field_definition) {
    /** @var \Drupal\organization_user\UserOrganizationResolver $resolver */
    $resolver = \Drupal::service('organization_user.organization_resolver');
    if ($entity instanceof EntityOwnerInterface && $owner = $entity->getOwner()) {
      return $resolver->getOrganization($owner) ? $resolver->getOrganization()->id() : NULL;
    }
    else {
      return $resolver->getOrganization() ? $resolver->getOrganization()->id() : NULL;
    }
  }
}
