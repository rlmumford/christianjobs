<?php

namespace Drupal\cj_membership\Form;

use Drupal\cj_membership\Entity\Membership;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

class MembershipPurchaseForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'membership_purchase_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();

    /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
    $membership_storage = \Drupal::entityTypeManager()->getStorage('cj_membership');
    $membership = $membership_storage->getAccountMembership($user);

    if (!$membership || ($membership->status != Membership::STATUS_ACTIVE)) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => (!$membership || $membership->status == Membership::STATUS_INACTIVE) ? new TranslatableMarkup("Join Now") : new TranslatableMarkup('Renew your Membership'),
      ];
    }

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_cart\CartProviderInterface $cart_provider */
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    /** @var \Drupal\commerce_cart\CartManagerInterface $cart_manager */
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $user = $this->currentUser();

    $cart = $cart_provider->getCart('default');
    if (!$cart) {
      $cart = $cart_provider->createCart('default');
    }

    /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
    $membership_storage = \Drupal::entityTypeManager()->getStorage('cj_membership');
    $membership = $membership_storage->getAccountMembership($user);
    if (!$membership) {
      /** @var \Drupal\cj_membership\Entity\Membership $membership */
      $membership = $membership_storage->create([
        'level' => Membership::LEVEL_FULL,
      ])->setOwnerId($user->id());
      $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }

    $cart_manager->addEntity($cart, $membership);
    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $cart->id(),
    ]);
  }
}
