<?php

namespace Drupal\contacts_jobs_credits;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class JobCreditOrderProcessor
 *
 * @package Drupal\contacts_jobs_credits
 */
class JobCreditOrderProcessor implements OrderProcessorInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $creditStorage;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * JobCreditOrderProcessor constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->creditStorage = $entity_type_manager->getStorage('cj_credit');
    $this->currentUser = $current_user;
  }

  /**
   * Processes an order.
   *
   * This method assumes that only one job is being posted per order AND that
   * a job credit covers the cost of the whole job.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function process(OrderInterface $order) {
    if (!$order->hasField('contacts_job')) {
      return;
    }

    $job = $order->contacts_job->entity;
    if (!$job) {
      return;
    }

    $available_credit_ids = $this->creditStorage->getQuery()
      ->condition('org', $job->organisation->target_id)
      ->condition('status', 'available')
      ->execute();
    if (empty($available_credit_ids)) {
      return;
    }

    $items = $order->getItems();
    $credit = $this->creditStorage->load(reset($available_credit_ids));

    foreach ($items as $item) {
      if (($entity = $item->getPurchasedEntity()) && $entity->bundle() === 'contacts_job_posting') {
        $adjustment_amount = $item->getAdjustedTotalPrice()->multiply(-1);

        if (!$job->credit->isEmptY()) {
          $credit = $job->credit->entity;
        }
        else {
          $credit->quantity_spent = $credit->quantity_spent->value + $item->getQuantity();
          if ($credit->quantity_spent->value >= $credit->quantity->value) {
            $credit->status = 'spent';
          }
          $credit->save();

          $job->credit = $credit;
          $job->save();
        }

        $item->addAdjustment(new Adjustment([
          'type' => 'promotion',
          'label' => new TranslatableMarkup('Job Credit'),
          'amount' => $adjustment_amount,
          'percentage' => '100',
          'source_id' => $credit->id(),
        ]));
      }
    }
  }
}
