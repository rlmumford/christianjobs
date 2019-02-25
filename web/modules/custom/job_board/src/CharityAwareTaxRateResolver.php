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
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\job_board\Entity\JobExtension;
use Drupal\job_role\Entity\JobRole;
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
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage = NULL;

  /**
   * CharityAwareTaxRateResolver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->profileStorage = $entity_type_manager->getStorage('profile');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(TaxZone $zone, OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    // Check that the order item entity is a job role.
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!(($purchased_entity instanceof JobRole) || ($purchased_entity instanceof JobExtension))) {
      return FALSE;
    }

    // Get the employer profile to check the charity profile.
    $user = $customer_profile->getOwner();
    $employer_profile = $this->profileStorage->loadDefaultByUser($user, 'employer');
    if (!$employer_profile) {
      return FALSE;
    }

    if (!$employer_profile->employer_is_charity->value) {
      return FALSE;
    }

    if ($zone->getId() != "gb") {
      return FALSE;
    }

    // Get the available rates for this zone.
    $rates = $zone->getRates();

    $resolved_rate = FALSE;
    foreach ($rates as $rate) {
      if ($rate->getId() == "zero") {
        $resolved_rate = $rate;
        break;
      }
    }

    return $resolved_rate;
  }
}
