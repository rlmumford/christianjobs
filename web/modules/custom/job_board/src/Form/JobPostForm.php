<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 28/08/2018
 * Time: 14:59
 */

namespace Drupal\job_board\Form;

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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Proceed to Checkout');
    $actions['submit']['#submit'][] = '::submitFormAddToCart';
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
      $membership_storage = \Drupal::service('entity_type.manager')->getStorage('cj_membership');
      $current_user = \Drupal::currentUser();
      if ($form_state->getValue(['membership', 'new'])) {
        $membership = $membership_storage->create()
          ->setOwnerId($current_user->id());
        $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      }
      elseif ($form_state->getValue(['membership', 'extend'])) {
        $membership_ids = $membership_storage->getQuery()
          ->condition('member.target_id', $current_user->id())
          ->execute();
        $membership = $membership_storage->load(reset($membership_ids));
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
}
