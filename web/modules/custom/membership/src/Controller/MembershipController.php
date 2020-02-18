<?php

namespace Drupal\cj_membership\Controller;

use Drupal\cj_membership\Entity\Membership;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MembershipController extends ControllerBase {

  /**
   * Page to post a new job.
   */
  public function buyMembership() {
    $cookies = [
      'membershipPurchaseRegister' => TRUE,
    ];

    $current_user = \Drupal::currentUser();
    if ($current_user->isAnonymous()) {
      user_cookie_save($cookies);
      return $this->redirect('user.register');
    }

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager()->getStorage('profile');
    if (!($profile = $profile_storage->loadDefaultByUser($current_user, 'employer')) || !$profile->employer_name->value) {
      user_cookie_save($cookies);
      return $this->redirect('job_board.employer_edit', ['user' => $current_user->id()]);
    }

    /** @var \Drupal\commerce_cart\CartProvider $cart_provider */
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $cart = $cart_provider->getCart('default');
    if (!$cart) {
      $cart = $cart_provider->createCart('default');
    }

    /** @var \Drupal\cj_membership\MembershipStorage $membership_storage */
    $membership_storage = \Drupal::entityTypeManager()->getStorage('cj_membership');
    $membership = $membership_storage->getAccountMembership($current_user);

    // Membership on current order.
    $membership_in_cart = FALSE;
    foreach ($cart->getItems() as $order_item) {
      if ($order_item->getPurchasedEntity() instanceof Membership) {
        $membership_in_cart = TRUE;
      }
    }

    $added = FALSE;
    if (!$membership_in_cart) {
      if (!$membership) {
        $membership = $membership_storage->create(['level' => Membership::LEVEL_FULL])->setOwnerId($current_user->id());
        $membership->start->value = date(DateTimeItemInterface::DATE_STORAGE_FORMAT);
        \Drupal::service('commerce_cart.cart_manager')->addEntity($cart, $membership);
        $added = TRUE;
      }
      else if ($membership->status->value != Membership::STATUS_ACTIVE) {
        \Drupal::service('commerce_cart.cart_manager')->addEntity($cart, $membership);
        $added = TRUE;
      }
    }

    user_cookie_delete('membershipPurchaseRegister');
    if ($added || $membership_in_cart) {
      return $this->redirect('commerce_checkout.form', [
        'commerce_order' => $cart->id(),
      ]);
    }
    else {
      return new RedirectResponse(Url::fromUri('internal:/membership')->toString());
    }
  }

}
