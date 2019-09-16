<?php

namespace Drupal\job_board;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\DrupalDateTime;
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
    if ($operation == 'delete') {
      $result = AccessResult::allowedIfHasPermission($account, 'delete any job_role');
      $result = $result->orIf(
        AccessResult::allowedIfHasPermission($account, 'delete own job_role')
          ->andIf(AccessResult::allowedIf($entity->owner->target_id == $account->id()))
      );
      $result->cachePerUser();
      $result->addCacheableDependency($entity);
      return $result;
    }
    if ($operation == 'boost') {
      return AccessResult::allowedIfHasPermission($account, 'boost any job_role');
    }
    if ($operation == 'extend') {
      $result = AccessResult::allowedIf(
        (
          $entity->owner->target_id == $account->id()
          && $account->hasPermission('extend own job_role')
          && $entity->paid_to_date->date
          && $entity->paid_to_date->date->format('Y-m-d') >= (new DrupalDateTime())->format('Y-m-d')
        )
        || $account->hasPermission('extend any job_role')
      );

      $result->addCacheContexts(['user.permissions']);
      $result->cachePerUser();
      $result->addCacheableDependency($entity);
      $result->setCacheMaxAge(60*60*24);

      return $result;
    }
    if ($operation == 'repost') {
      $result = AccessResult::allowedIf(
        (
          $entity->owner->target_id == $account->id()
          && $account->hasPermission('repost own job_role')
          && $entity->paid_to_date->date
          && $entity->paid_to_date->date->format('Y-m-d') < (new DrupalDateTime())->format('Y-m-d')
        )
        || $account->hasPermission('repost any job_role')
      );

      $result->addCacheContexts(['user.permissions']);
      $result->cachePerUser();
      $result->addCacheableDependency($entity);
      $result->setCacheMaxAge(60*60*24);

      return $result;
    }

    return parent::checkAccess($entity, $operation, $account);
  }
}
