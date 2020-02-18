<?php

namespace Drupal\job_board\EventSubscriber;

use Drupal\cj_membership\Entity\Membership;
use Drupal\commerce_gocardless\Event\CheckoutPaymentsEvent;
use Drupal\commerce_gocardless\Event\CommerceGoCardlessEvents;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GoCardlessSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    if (class_exists(CommerceGoCardlessEvents::class)) {
      return [
        CommerceGoCardlessEvents::CHECKOUT_PAYMENTS => 'checkoutPayments',
      ];
    }

    return [];
  }

  /**
   * Collection the checkout payments.
   *
   * @param \Drupal\commerce_gocardless\Event\CheckoutPaymentsEvent $event
   */
  public function checkoutPayments(CheckoutPaymentsEvent $event) {
    $order = $event->getOrder();

    // @todo: This is a bit of a hack. Get the order number *before* we send
    //        stuff to gc.
    if (!$order->getOrderNumber()) {
      $order->setOrderNumber(
        'CJ' . date('y') . str_pad($order->id(), 6, "0", STR_PAD_LEFT)
      );
    }

    $payments = $event->getPayments();
    $initial_payment = &$payments[0];

    /** @var \Drupal\commerce_order\Entity\OrderItem $item */
    foreach ($order->getItems() as $item) {
      if ($item->hasPurchasedEntity() && ($membership = $item->getPurchasedEntity()) && ($membership instanceof Membership)) {
        // Get the value with VAT.
        $value = Calculator::multiply($item->getTotalPrice()->getNumber(), 1.2);

        $monthly_part = Calculator::ceil(Calculator::divide($value, 12));
        $total_of_months = Calculator::multiply($monthly_part, 12);
        $diff = Calculator::subtract($total_of_months, $value);
        $last_month = Calculator::subtract($monthly_part, $diff);

        $amounts = array_fill(0, 11, (int) Calculator::multiply($monthly_part, 100));
        $amounts[12] = (int) Calculator::multiply($last_month, 100);

        $direct_debit_payment = [
          'type' => 'instalment_schedule',
          'name' => $membership->label(),
          'price' => new Price($value, $item->getTotalPrice()->getCurrencyCode()),
          'schedule' => [
            'start_date' => (new DrupalDateTime())->format('Y-m-d'),
            'interval_unit' => 'monthly',
            'interval' => 1,
            'amounts' => array_values($amounts),
          ],
          'metadata' => (object) [
            'membership' => $membership->id(),
          ],
          'idempotency_key' => 'membership_schedule_'.$membership->id().'_'.date('Y'),
        ];
        $payments[] = $direct_debit_payment;

        /** @var Price $initial_price */
        $initial_price = $initial_payment['price'];
        $initial_payment['price'] = $initial_price->subtract(new Price($value, $item->getTotalPrice()->getCurrencyCode()));
      }
    }

    $event->setPayments($payments);
  }
}
