<?php

namespace Drupal\job_board;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\SynchronizableEntityTrait;
use Drupal\Core\Entity\SynchronizableInterface;
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
    return $this->label();
  }

  /**
   * Gets the purchasable entity's price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The price, or NULL.
   */
  public function getPrice(Context $context = NULL) {
    if ($this->rpo->value) {
      $price = new Price('695.00', 'GBP');
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
    $start_date = $this->publish_date->date;
    $end_date = $this->end_date->date;
    $current_date = new DrupalDateTime();

    return ($current_date > $start_date) && ($current_date < $end_date);
  }

  /**
   * Set whether the job is active.
   */
  public function setActive($active) {}

}
