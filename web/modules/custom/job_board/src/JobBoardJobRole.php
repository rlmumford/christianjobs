<?php

namespace Drupal\job_board;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\job_role\Entity\JobRole;

class JobBoardJobRole extends JobRole implements PurchasableEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if ($this->paid->value && !$this->end_date->value) {
      $package_info = job_board_job_package_info($this->package->value);

      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = clone $this->publish_date->date;
      $end_date->add(new \DateInterval($package_info['duration']));
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
  public function getPrice() {
    $package = job_board_job_package_info($this->package->value ?: 'basic');

    /** @var \Drupal\commerce_price\Price $price */
    $price = $package['price'];

    if ($this->featured_dates->count() > $package['allowed_featured_dates']) {
      $price = $price
        ->add(
          (new Price('20.00', 'GBP'))
            ->multiply($this->featured_dates->count() - $package['allowed_featured_dates'])
        );
    }

    return $price;
  }
}
