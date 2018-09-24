<?php

namespace Drupal\cj_membership\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\cj_membership\Entity\Membership;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
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

      $membership = $item->getPurchasedEntity();
      if (!($membership instanceof Membership)) {
        continue;
      }

      if ($membership->status->value == Membership::STATUS_INACTIVE) {
        // The membership has never been active before.
        $membership->start = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);

        /** @var \Drupal\Core\Datetime\DrupalDateTime $expiry_date */
        $expiry_date = clone $membership->start->date;
        $expiry_date->add(new \DateInterval('P1Y'));
        $membership->expiry->value = $expiry_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      }
      else {
        $membership->extended = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);

        /** @var \Drupal\Core\Datetime\DrupalDateTime $expiry_date */
        $expiry_date = clone $membership->extended->date;
        $expiry_date->add(new \DateInterval('P1Y'));
        $membership->expiry->value = $expiry_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      }

      $membership->status = Membership::STATUS_ACTIVE;
      $membership->save();
    }
  }
}
