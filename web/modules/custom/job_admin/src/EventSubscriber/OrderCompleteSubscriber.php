<?php

namespace Drupal\job_admin\EventSubscriber;

use Drupal\cj_membership\Entity\Membership;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\job_board\JobBoardJobRole;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * OrderCompleteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'][] = ['onOrderCompleteHandler'];

    return $events;
  }

  /**
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   */
  public function onOrderCompleteHandler(WorkflowTransitionEvent $event) {
    /** @var Order $order */
    $order = $event->getEntity();
    $membership = NULL;
    $job_posts = [];
    $rpos = [];

    foreach ($order->getItems() as $item) {
      /** @var OrderItemInterface $item */
      if (!$item->hasPurchasedEntity()) {
        continue;
      }

      $entity = $item->getPurchasedEntity();
      if ($entity instanceof Membership) {
        $membership = $entity;
        continue;
      }

      if (!($entity instanceof JobBoardJobRole)) {
        continue;
      }

      if ($entity->rpo->value) {
        $rpos[] = $entity;
      }
      $job_posts[] = $entity;
    }

    // Assign an account manager.
    $account_managers = $this->getStorage('user')->getQuery()
      ->condition('status', 1)
      ->condition('roles','account_manager')
      ->execute();

    // Work out if there is a membership service.
    $membership_service = NULL;
    if ($membership) {
      $query = $this->getStorage('service')->getQuery();
      $query->condition('type', 'membership');
      $query->condition('membership.target_id', $membership->id());
      $result = $query->execute();

      if (empty($result)) {

        $membership_service = $this->getStorage('service')->create([
          'state' => TRUE,
          'type' => 'membership',
          'membership' => $membership->id(),
          'manager' => reset($account_managers),
          'recipients' => [ $membership->getOwnerId() ],
        ]);
      }
      else {
        $membership_service = $this->getStorage('service')->load(reset($result));
        $membership_service->state = TRUE;
      }

      $membership_service->save();
    }
    else {
      $poster = \Drupal::currentUser()->id();

      $query = $this->getStorage('service')->getQuery();
      $query->condition('type', 'membership');
      $query->condition('membership.entity.member.target_id', $poster);
      $query->condition('membership.entity.status', Membership::STATUS_ACTIVE);

      if ($membership_result = $query->execute()) {
        $membership_service = $this->getStorage('service')->load(reset($membership_result));
      }
    }

    $account_manager = $membership_service ? $membership_service->manager->target_id : reset($account_managers);

    // Create a job_post_set_support
    if ($job_posts) {
      $first_job = reset($job_posts);
      $set_service = $this->getStorage('service')->create([
        'state' => TRUE,
        'type' => 'job_post_set_support',
        'job_posts' => $job_posts,
        'manager' => $account_manager,
        'recipients' => [ $first_job->getOwnerId() ],
      ]);
      $set_service->save();
    }

    // Create RPOs
    foreach ($rpos as $rpo) {
      $rpo_service = $this->getStorage('service')->create([
        'state' => TRUE,
        'type' => 'rpo',
        'job_post' => $rpo,
        'manager' => $account_manager,
        'recipients' => [ $rpo->getOwnerId() ],
      ]);
      $rpo_service->save();
    }
  }

  /**
   * Get storage for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *
   * @throws
   */
  protected function getStorage($entity_type_id) {
    return $this->entityTypeManager->getStorage($entity_type_id);
  }
}
