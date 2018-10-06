<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block that displays page header.
 *
 * @Block(
 *   id = "cj_menu_toggle",
 *   admin_label = @Translation("Christian Jobs Menu Toggle"),
 * )
 */
class CJMenuToggle extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#prefix' => '<div id="navbar-menu-toggle">',
      '#markup' => '<a href="#"><i class="material-icons navbar-icon">menu</i></a>',
      '#suffix' => '</div>',
    ];
  }

}
