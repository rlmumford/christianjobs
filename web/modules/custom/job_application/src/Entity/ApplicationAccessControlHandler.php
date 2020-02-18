<?php

namespace Drupal\job_application\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class ApplicationAccessControlHandler
 *
 * @package Drupal\job_application\Entity
 */
class ApplicationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    EntityInterface $entity,
    $operation,
    AccountInterface $account
  ) {
    /** @var \Drupal\job_application\Entity\Application $entity */
    $access = parent::checkAccess($entity, $operation, $account);

    if ($operation === 'view') {
      $access = $access->orIf(
        AccessResult::allowedIf($entity->getOwnerId() == $account->id())
          ->cachePerUser()
          ->addCacheableDependency($entity)
      );
      $access = $access->orIf(
        AccessResult::allowedIf($entity->job->entity->getOwnerId() == $account->id())
          ->cachePerUser()
          ->addCacheableDependency($entity->job->entity)
      );

      return $access;
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $access = parent::checkCreateAccess($account, $context, $entity_bundle);
    $access->orIf(AccessResult::allowedIfHasPermission($account, 'apply for jobs'));
    return $access;
  }
}
