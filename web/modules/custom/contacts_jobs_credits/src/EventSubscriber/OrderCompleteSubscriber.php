<?php

namespace Drupal\contacts_jobs_credits\EventSubscriber;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * The credit entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $creditStorage;

  /**
   * OrderCompleteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->creditStorage = $entity_type_manager->getStorage('jcj_credit');
  }

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
      if ($entity instanceof ProductVariation && $entity->bundle() === 'credit_bundle') {
        $credit = $this->creditStorage->create([
          'expires' => (new DrupalDateTime())->add(new \DateInterval('P1Y'))->format(DateTimeItem::DATE_STORAGE_FORMAT),
          'status' => 'available',
          'quantity' => $entity->credit_count->value,
          'owner' => $order->getCustomerId(),
          'org' => $order->getCustomerId(), // @todo: Fix how to get the org id.
        ]);
        $credit->save();
      }
    }
  }
}
