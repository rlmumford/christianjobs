<?php

namespace Drupal\contacts_jobs_credits;

use Drupal\contacts_jobs_credits\Entity\JobCredit;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\organization\Entity\Organization;
use Drupal\user\Entity\User;

class JobCreditManager implements JobCreditManagerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * JobCreditManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAvailableCredit(User $poster): bool {
    $available_credit = $this->entityTypeManager
      ->getStorage('job_board_job_credit')
      ->getQuery()
      ->condition('org', $poster->id())
      ->condition('status', 'available')
      ->count()
      ->execute();

    return ($available_credit > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableCredit(User $poster) : ?JobCredit {
    $storage = $this->entityTypeManager->getStorage('job_board_job_credit');
    $ids = $storage->getQuery()
      ->condition('org', $poster->id())
      ->condition('status', 'available')
      ->range(0, 1)
      ->execute();

    return $storage->load(reset($ids));
  }
}
