<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block that displays page header.
 *
 * @Block(
 *   id = "cj_drawer_title",
 *   admin_label = @Translation("Christian Jobs Drawer Title"),
 * )
 */
class CJDrawerTitle extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['close'] = [
      '#type' => 'container',
      '#id' => 'drawer-menu-close',
      'toggle' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'href' => '#',
        ],
        '#value' => '<i class="material-icons drawer-icon">arrow_back</i>',
      ],
    ];
    $build['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'drawer-title',
      ],
      '#id' => 'drawer-title',
      '#value' => new TranslatableMarkup('Menu'),
    ];

    return $build;
  }
}
