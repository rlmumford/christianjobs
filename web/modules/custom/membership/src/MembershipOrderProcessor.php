<?php

namespace Drupal\cj_membership;

use Drupal\cj_membership\Entity\Membership;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\job_board\JobBoardJobRole;

/**
 * Class MembershipOrderProcessor
 *
 * @package Drupal\cj_membership
 */
class MembershipOrderProcessor implements OrderProcessorInterface {

  /**
   * The membership storage.
   *
   * @var \Drupal\cj_membership\MembershipStorage
   */
  protected $membershipStorage;

  /**
   * MembershipOrderProcessor constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->membershipStorage = $entity_type_manager->getStorage('cj_membership');
  }

  /**
   * Processes an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function process(OrderInterface $order) {
    $membership = NULL;
    $one_free_job = FALSE;

    // This membership is being bought this time.
    $membership_in_cart = FALSE;
    foreach ($order->getItems() as $item) {
      if ($item->getPurchasedEntity() instanceof Membership) {
        $membership_in_cart = $item->getPurchasedEntity();
        $one_free_job = TRUE;
      }
    }

    if (!$membership_in_cart) {
      $membership = $this->membershipStorage->getAccountMembership($order->getCustomer());
    }

    if ($membership_in_cart || ($membership && $membership->status->value == Membership::STATUS_ACTIVE)) {
      foreach ($order->getItems() as $item) {
        $entity = $item->getPurchasedEntity();
        if (!($entity instanceof JobBoardJobRole)) {
          continue;
        }

        if ($entity->isRpo()) {
          continue;
        }

        $adjustment_amount = $item->getAdjustedUnitPrice()->multiply($one_free_job ? '-1' : '-0.25');
        $adjustment_amount = \Drupal::service('commerce_price.rounder')->round($adjustment_amount);

        $item->addAdjustment(new Adjustment([
          'type' => 'promotion',
          'label' => $one_free_job ? new TranslatableMarkup('First Membership Job Free!') : new TranslatableMarkup('Membership Discount'),
          'amount' => $adjustment_amount,
          'percentage' => $one_free_job ? '100' : '25',
          'source_id' => $membership ? $membership->id() : $membership_in_cart->id(),
        ]));

        $one_free_job = FALSE;
      }
    }
  }
}
