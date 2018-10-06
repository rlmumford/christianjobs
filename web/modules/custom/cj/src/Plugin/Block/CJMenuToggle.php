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
      '#type' => 'container',
      '#id' => 'navbar-menu-toggle',
      'toggle' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'href' => '#',
        ],
        '#value' => '<i class="material-icons navbar-icon">menu</i>',
      ],
    ];
  }

}
