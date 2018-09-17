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
        $entity->owner->target_id = $account->id()
        || ($entity->isActive() && $account->hasPermission('view published jobs'))
        || (!$entity->isActive() && $account->hasPermission('view unpublished jobs'))
      );
      $result->addCacheContexts(['user.permissions']);
      $result->cachePerUser();
      $result->addCacheableDependency($entity);
      $result->setCacheMaxAge(60*60*24);
    }

    return parent::checkAccess($entity, $operation, $account);
  }
}
