<?php

namespace Drupal\job_board;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\organization_user\UserOrganizationResolver;

class JobCreditOrderProcessor implements OrderProcessorInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $creditStorage;

  /**
   * @var \Drupal\organization_user\UserOrganizationResolver
   */
  protected $organizationResolver;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * JobCreditOrderProcessor constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UserOrganizationResolver $organization_resolver, AccountInterface $current_user) {
    $this->creditStorage = $entity_type_manager->getStorage('job_credit');
    $this->organizationResolver = $organization_resolver;
    $this->currentUser = $current_user;
  }

  /**
   * Processes an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function process(OrderInterface $order) {
    $organization = $this->organizationResolver->getOrganization($this->currentUser);

    $available_credit_ids = $this->creditStorage->getQuery()
      ->condition('organization', $organization->id())
      ->condition('status', 'available')
      ->execute();
    if (empty($available_credit_ids)) {
      return;
    }

    $items = $order->getItems();
    $available_credits = $this->creditStorage->loadMultiple(
      array_slice($available_credit_ids, 0, count($items))
    );

    foreach ($items as $item) {
      $entity = $item->getPurchasedEntity();
      if (!($entity instanceof JobBoardJobRole)) {
        continue;
      }

      if ($entity->isRpo()) {
        continue;
      }

      if (!$entity->job_credit->isEmpty() || count($available_credits)) {
        $adjustment_amount = $item->getAdjustedUnitPrice()->multiply(-1);

        if (!$entity->job_credit->isEmpty()) {
          $credit = $entity->job_credit->entity;
        }
        else {
          $credit = array_shift($available_credits);
          $entity->job_credit = $credit;
        }
        $credit->status = 'spent';

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
