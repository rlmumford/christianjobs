<?php

namespace Drupal\job_board\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\job_board\Entity\JobExtension;
use Drupal\job_board\JobBoardJobRole;
use Drupal\organization_user\UserOrganizationResolver;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $creditStorage;

  /**
   * @var \Drupal\organization_user\UserOrganizationResolver
   */
  protected $organizationResolver;

  /**
   * OrderCompleteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UserOrganizationResolver $organization_resolver) {
    $this->creditStorage = $entity_type_manager->getStorage('job_board_job_credit');
    $this->organizationResolver = $organization_resolver;
  }

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
