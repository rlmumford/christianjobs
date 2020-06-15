<?php

namespace Drupal\job_board;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\organization\Entity\Organization;

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
  public function hasAvailableCredit(Organization $organization) {
    // @todo: find a way to lock credits

    $available_credit = $this->entityTypeManager
      ->getStorage('job_board_job_credit')
      ->getQuery()
      ->condition('organization', $organization->id())
      ->condition('status', 'available')
      ->count()
      ->execute();

    return ($available_credit > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableCredit(Organization $organization) {
    $storage = $this->entityTypeManager->getStorage('job_board_job_credit');
    $ids = $storage->getQuery()
      ->condition('organization', $organization->id())
      ->condition('status', 'available')
      ->range(0, 1)
      ->execute();

    return $storage->load(reset($ids));
  }
}
