<?php

namespace Drupal\contacts_jobs_dashboard\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Jobboard Dashboard event subscriber.
 */
class OrderAccessSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct the event subscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Allow access to a user's organisation's orders.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The access event.
   */
  public function onQueryAccess(QueryAccessEvent $event) {
    if ($event->getOperation() === 'view') {
      $conditions = $event->getConditions();

      // If not always false, but there are no conditions, the user should have
      // unrestricted access, so do nothing.
      if (!$conditions->isAlwaysFalse() && $conditions->count() === 0) {
        return;
      }

      /** @var \Drupal\user\UserInterface $user */
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($event->getAccount()->id());

      $organisation_ids = [];
      $dependencies = [];
      foreach ($user->get('organisations') as $item) {
        $dependencies[] = $item->entity;
        $group = $item->membership->getGroup();
        $organisation_ids[] = $group->get('contacts_org')->target_id;
      }

      if (!empty($organisation_ids)) {
        $conditions->addCondition('uid', $organisation_ids, 'IN');
        foreach ($dependencies as $dependency) {
          $conditions->addCacheableDependency($dependency);
        }
      }
      $conditions->addCacheTags(['group_content_list']);
    }

  }

  /**
   * Implementation of the order access hook.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $operation
   *   The operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account performing the action.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \contacts_jobs_dashboard_commerce_order_access()
   */
  public function hookAccess(OrderInterface $order, string $operation, AccountInterface $account): AccessResultInterface {
    if ($operation !== 'view') {
      return AccessResult::neutral();
    }

    $customer = $order->getCustomer();
    if (!$customer->hasRole('crm_org')) {
      return AccessResult::neutral()
        // Use the group content list tag to invalidate on new memberships.
        ->addCacheableDependency($customer);
    }

    // Find the membership for this organisation.
    /** @var \Drupal\group\GroupMembership|null $membership */
    $membership = NULL;
    foreach (User::load($account->id())->get('organisations') as $item) {
      $group = $item->membership->getGroup();
      if ($group->get('contacts_org')->target_id == $customer->id()) {
        $membership = $item->membership;
        break;
      }
    }

    // Use the group content list to trigger invalidation on new memberships.
    if (!$membership) {
      return AccessResult::neutral()
        ->addCacheTags(['group_content_list']);
    }

    // Check the finance permission.
    return AccessResult::allowedIf($membership->hasPermission('make job payments'))
      // Make the group content the cacheable dependency.
      ->addCacheableDependency($item->entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'entity.query_access.commerce_order' => ['onQueryAccess'],
    ];
  }

}
