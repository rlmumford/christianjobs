<?php

namespace Drupal\cj_membership\Plugin\Commerce\Condition;

use Drupal\cj_membership\Entity\Membership;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides commerce condition for Job Post items.
 *
 * @CommerceCondition(
 *   id = "order_item_membership",
 *   label = @Translation("Membership"),
 *   display_label = @Translation("Membership"),
 *   category = @Translation("Membership"),
 *   entity_type = "commerce_order_item",
 *   weight = -1,
 * )
 */
class OrderItemMembership extends ConditionBase {

  /**
   * Evaluates the condition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    $membership = $order_item->getPurchasedEntity();

    return ($membership instanceof Membership);
  }

}
