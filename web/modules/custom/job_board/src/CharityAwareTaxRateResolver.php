<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 25/02/2019
 * Time: 13:13
 */

namespace Drupal\job_board;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_tax\Resolver\DefaultTaxRateResolver;
use Drupal\commerce_tax\TaxZone;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Class CharityAwareTaxRateResolver
 *
 * Detect whether an organisation is a registered charity in the UK,
 * If it is, cancel VAT on job adverts.
 *
 * @package Drupal\job_board
 */
class CharityAwareTaxRateResolver extends DefaultTaxRateResolver {

  /**
   * {@inheritdoc}
   */
  public function resolve(TaxZone $zone, OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    $rates = $zone->getRates();

    dpm($rates, "Rates");
    // Take the default rate, or fallback to the first rate.
    $resolved_rate = reset($rates);
    foreach ($rates as $rate) {
      if ($rate->isDefault()) {
        $resolved_rate = $rate;
        break;
      }
    }
    return $resolved_rate;
  }
}
