<?php

namespace Drupal\job_admin\Plugin\Commerce\Condition;

use Drupal\commerce_order\Plugin\Commerce\Condition\OrderCustomerRole;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the current user role role condition for orders.
 *
 * @CommerceCondition(
 *   id = "current_user_role",
 *   label = @Translation("Acting user role"),
 *   category = @Translation("Acting User"),
 *   entity_type = "commerce_order",
 *   weight = -1,
 * )
 */
class CurrentUserRole extends OrderCustomerRole {

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);

    return (bool) array_intersect($this->configuration['roles'], \Drupal::currentUser()->getRoles());
  }
}
