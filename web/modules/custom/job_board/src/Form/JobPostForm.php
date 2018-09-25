<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 28/08/2018
 * Time: 14:59
 */

namespace Drupal\job_board\Form;

use Drupal\cj_membership\Entity\Membership;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class JobPostForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getOperation() {
    return 'post';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\commerce_cart\CartProvider $cart_provider */
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $cart = $cart_provider->getCart('default');
    if (!$cart) {
      $cart = $cart_provider->createCart('default');
    }

    // Add a membership options to this form.
    if (\Drupal::moduleHandler()->moduleExists('cj_membership')) {
      /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
      $membership_storage = \Drupal::entityTypeManager()->getStorage('cj_membership');
      $membership = $membership_storage->getAccountMembership(\Drupal::currentUser());

      // Membership on current order.
      $membership_in_cart = FALSE;
      foreach ($cart->getItems() as $order_item) {
        if ($order_item->getPurchasedEntity() instanceof Membership) {
          $membership_in_cart = TRUE;
        }
      }

      if (!$membership_in_cart && !$membership) {
        $form['membership']['#type'] = 'container';
        $form['membership']['new'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Become a Christian Jobs Member'),
        ];
      }
      else if (!$membership_in_cart && $membership && $membership->status->value == Membership::STATUS_EXPIRED) {
        $form['membership']['#type'] = 'container';
        $form['membership']['extend'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Renew Membership'),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#submit'][] = '::submitFormAddToCart';

    $actions['submit_another'] = $actions['submit'];
    $actions['submit_another']['#value'] = $this->t('Save & Post Another Job');
    $actions['submit_another']['#submit'][] = '::submitFormRedirectToJobPost';

    $actions['submit']['#value'] = t('Proceed to Payment');
    $actions['submit']['#submit'][] = '::submitFormRedirectToCheckout';

    return $actions;
  }

  public function submitFormAddToCart(array $form, FormStateInterface $form_state) {
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    /** @var \Drupal\commerce_cart\CartManagerInterface $cart_manager */
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $cart = $cart_provider->getCart('default');
    if (!$cart) {
      $cart = $cart_provider->createCart('default');
    }
    $cart_manager->addEntity($cart, $this->getEntity());

    // If the membership options have been selected then add the membership to
    // the cart.
    if (\Drupal::service('module_handler')->moduleExists('cj_membership')) {
      /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
      $membership_storage = \Drupal::service('entity_type.manager')->getStorage('cj_membership');
      $current_user = \Drupal::currentUser();
      if ($form_state->getValue(['membership', 'new'])) {
        $membership = $membership_storage->create()
          ->setOwnerId($current_user->id());
        $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      }
      elseif ($form_state->getValue(['membership', 'extend'])) {
        $membership = $membership_storage->getAccountMembership($current_user);
      }

      if ($membership) {
        $cart_manager->addEntity($cart, $membership);
      }
    }
  }

  public function submitFormRedirectToCheckout(array $form, FormStateInterface $form_state) {
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $cart_provider->getCart('default')->id(),
    ]);
  }

  public function submitFormRedirectToJobMost(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('job_board.post');
  }
}
