<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block that displays page header.
 *
 * @Block(
 *   id = "cj_user_button",
 *   admin_label = @Translation("Christian Jobs User Button"),
 * )
 */
class CJUserButton extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $class = \Drupal::currentUser()->isAuthenticated() ? 'user-authenticated' : 'user-anonymous';
    $title = \Drupal::currentUser()->isAuthenticated() ? 'Your Dashboard' : 'Log-in';
    $markup = '<a title="'.$title.'" href="/user" rel="no-follow"><i class="material-icons navbar-icon '.$class.'">account_circle</i></a>';

    if ($cart = \Drupal::service('commerce_cart.cart_provider')->getCart('default')) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      $item_count = count($cart->getItems());
      $items_class = $item_count > 0 ? 'has-items has-'.$item_count.'-items' : 'no-items';
      $markup = '<a title="Your Cart" href="/cart" rel="no-follow"><i item-count="'.$item_count.'" class="material-icons navbar-icon cart-icon '.$items_class.'">shopping_basket</i></a>' . $markup;
    }

    return [
      '#prefix' => '<div id="navbar-user-button">',
      '#markup' => $markup,
      '#suffix' => '</div>',
      '#cache' => [
        'contexts' => ['cart'],
      ],
    ];
  }

}
