<?php

namespace Drupal\job_board\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\job_board\Entity\JobExtension;
use Drupal\job_board\JobBoardJobRole;
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

  public function onOrderCompleteHandler(WorkflowTransitionEvent $event) {
    /** @var Order $order */
    $order = $event->getEntity();

    foreach ($order->getItems() as $item) {
      /** @var OrderItemInterface $item */
      if (!$item->hasPurchasedEntity()) {
        continue;
      }

      $job = $item->getPurchasedEntity();
      if ($job instanceof JobBoardJobRole) {
        $job->paid->value = TRUE;
        $job->save();
      }
      else if ($job instanceof JobExtension) {
        $job->paid->value = TRUE;
        $job->save();
      }
    }
  }
}
