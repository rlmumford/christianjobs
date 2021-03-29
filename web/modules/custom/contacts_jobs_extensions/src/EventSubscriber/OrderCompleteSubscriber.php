<?php

namespace Drupal\contacts_jobs_extensions\EventSubscriber;

use Drupal\contacts_jobs_extensions\Entity\JobExtension;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderCompleteSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'][] = ['onOrderCompleteHandler'];

    return $events;
  }

  /**
   * Act when an order is complete.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onOrderCompleteHandler(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $event->getEntity();

    foreach ($order->getItems() as $item) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
      if (!$item->hasPurchasedEntity()) {
        continue;
      }

      $entity = $item->getPurchasedEntity();
      if ($entity instanceof JobExtension) {
        $entity->paid = TRUE;
        $entity->save();
      }
    }
  }
}
