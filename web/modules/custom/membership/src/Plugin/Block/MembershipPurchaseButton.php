<?php

namespace Drupal\cj_membership\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Class MembershipPurchaseButton
 *
 * @Block(
 *   id = "membership_purchase_button",
 *   admin_label = @Translation("Membership Purchase Button"),
 *   category = @Translation("Membership"),
 * )
 */
class MembershipPurchaseButton extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\cj_membership\Form\MembershipPurchaseForm');
    return $form;
  }

  /**
   * 
   */

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('user'));
  }
}
