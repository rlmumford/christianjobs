<?php

namespace Drupal\job_application\Entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ApplicationQuestionAnswerAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Hand access checks off to the application.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\job_application\Entity\ApplicationQuestionAnswer $entity */
    return $entity->getApplication()->access($operation, $account);
  }

}
