<?php

namespace Drupal\cj_membership;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\EntityAccessControlHandler;

class MembershipAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      $result = AccessResult::allowedIf($entity->member->target_id == $account->id());
      $result->cachePerUser();
      $result->addCacheableDependency($entity);

      return $result;
    }

    return parent::checkAccess($entity, $operation, $account);
  }
}
