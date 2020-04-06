<?php

namespace Drupal\cj_membership\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class DonationReferral
 *
 * @ContentEntityType(
 *   id = "cj_membership_donation_ref",
 *   label = @Translation("Donation Referral"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "cj_membership_donation_ref",
 *   admin_permission = "administer donation referrals",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   }
 * )
 *
 *
 * @package Drupal\cj_membership\Entity
 */
class DonationReferral extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Referred User'))
      ->setSetting('target_type', 'user');

    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Member'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Time'));

    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('IP Address'));

    return $fields;
  }

}
