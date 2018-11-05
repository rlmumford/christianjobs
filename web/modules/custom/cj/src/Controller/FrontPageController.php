<?php

namespace Drupal\cj\Controller;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Controller\ControllerBase;

class FrontPageController extends ControllerBase {

  /**
   * Get the front page content.
   */
  public function frontPage() {
    $build = [];

    // @todo: Featured jobs.

    // Pricing.
    /** @var CurrencyFormatterInterface $currency_formatter */
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');
    $row = [
      '#type' => 'container',
      '#attached' => [
        'library' => ['job_board/pricing'],
      ],
      '#attributes' => [
        'class' => ['packages', 'z-level-3'],
      ],
    ];
    $packages = job_board_job_package_info();
    foreach ($packages as $key => $package) {
      /** @var Price $price */
      $price = $package['price'];

      $row[$key] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['package'],
        ]
      ];
      $row[$key]['title'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['package-title-container'],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#attributes' => [
            'class' => ['package-title'],
          ],
          '#value' => $package['label'],
        ],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attrbitues' => [
            'class' => ['package-description'],
          ],
          '#value' => $package['description'],
        ],
        'price' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'package-price',
              ($price instanceof Price) ? 'price-currency' : 'price-string',
            ],
          ],
          '#value' => ($price instanceof Price) ? $currency_formatter->format($price->getNumber(), $price->getCurrencyCode()) : $price,
        ],
        'cta' => [
          '#type' => 'link',
          '#title' => $package['cta_text'],
          '#url' => $package['cta_url'],
          '#attributes' => [
            'class' => ['button-sm', 'button', 'package-cta'],
          ],
        ],
      ];
    }
    $build['pricing'] = $row;

    // @todo: References.

    return $build;
  }
}
