<?php

namespace Drupal\contacts_jobs_dashboard\EventSubscriber;

use Drupal\contacts_jobs\Event\JobPostingFlowEvent;
use Drupal\contacts_jobs\InvalidWorkflowException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * Subscriber for adjusting the job posting flow.
 */
class JobPostingFlowSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The computed organisations for the current user.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $organisations;

  /**
   * Construct the subscription job posting flow subscriber.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      JobPostingFlowEvent::NAME => [
        ['removeEmployer'],
      ],
    ];
  }

  /**
   * Remove the employer step as we never manage in the posting flow.
   *
   * @param \Drupal\contacts_jobs\Event\JobPostingFlowEvent $event
   *   The job posting flow event.
   */
  public function removeEmployer(JobPostingFlowEvent $event): void {
    if (!$event->getOrganisation()) {
      $organisations = $this->getUserOrganisations();
      $organisation = reset($organisations);
      if ($organisation) {
        $event->setOrganisation($organisation);
      }
    }

    // If we still don't have an organisation, redirect to the user dashboard.
    if (!$event->getOrganisation()) {
      $this->messenger->addWarning('You must be part of an organisation to post a job.');
      throw new InvalidWorkflowException('Unable to find a valid organisation.');
    }

    $event->removeStep('employer');
  }

  /**
   * Get the organisations for the current user.
   *
   * @return \Drupal\user\UserInterface[]
   *   An array of organisations.
   */
  protected function getUserOrganisations(): array {
    if (isset($this->organisations)) {
      return $this->organisations;
    }

    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());

    $this->organisations = [];
    foreach ($user->get('organisations') as $item) {
      /** @var \Drupal\group\GroupMembership $membership */
      $membership = $item->membership;

      if ($membership->hasPermission('manage organisation jobs')) {
        $item = $membership->getGroup()->get('contacts_org');
        $this->organisations[$item->target_id] = $item->entity;
      }
    }
    return $this->organisations;
  }

}
