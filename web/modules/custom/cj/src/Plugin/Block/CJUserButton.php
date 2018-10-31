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
    $title = \Drupal::currentUser()->isAuthenticated() ? 'Log-in' : 'Your Dashboard';

    return [
      '#prefix' => '<div id="navbar-user-button">',
      '#markup' => '<a title="'.$title.'" href="/user" rel="no-follow"><i class="material-icons navbar-icon '.$class.'">account_circle</i></a>',
      '#suffix' => '</div>',
    ];
  }

}
