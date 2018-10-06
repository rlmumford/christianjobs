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
      '#prefix' => '<div id="drawer-menu-close">',
      '#suffix' => '</div>',
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#attributes' => [
        'href' => '#',
      ],
      '#value' => '<i class="material-icons drawer-icon">arrow_back</i>',
    ];
    $build['title'] = [
      '#prefix' => '<div id="drawer-title">',
      '#suffix' => '</div>',
      '#markup' => new TranslatableMarkup('Menu'),
    ];

    return $build;
  }
}
