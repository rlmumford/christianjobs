<?php

namespace Drupal\job_board;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\job_role\JobRoleAccessControlHandler;

class JobBoardJobRoleAccessControlHandler extends JobRoleAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      $result = AccessResult::allowedIf(
        $entity->owner->target_id == $account->id()
        || ($entity->isActive() && $account->hasPermission('view published jobs'))
        || (!$entity->isActive() && $account->hasPermission('view unpublished jobs'))
      );
      $result->addCacheContexts(['user.permissions']);
      $result->cachePerUser();
      $result->addCacheableDependency($entity);
      $result->setCacheMaxAge(60*60*24);

      return $result;
    }
    if ($operation == 'update') {
      $result = AccessResult::allowedIfHasPermission($account, 'update any job_role');
      $result = $result->orIf(
        AccessResult::allowedIfHasPermission($account, 'update own job_role')
          ->andIf(AccessResult::allowedIf($entity->owner->target_id == $account->id()))
      );
      $result->cachePerUser();
      $result->addCacheableDependency($entity);
      return $result;
    }
    if ($operation == 'boost') {
      return AccessResult::allowedIfHasPermission($account, 'boost any job_role');
    }

    return parent::checkAccess($entity, $operation, $account);
  }
}
