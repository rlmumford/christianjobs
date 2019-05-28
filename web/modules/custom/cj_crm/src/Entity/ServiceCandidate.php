<?php

namespace Drupal\cj_crm\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Class ServiceCandidate
 *
 * @ContentEntityType(
 *   id = "cj_service_candidate",
 *   label = @Translation("Service Candidate"),
 *   base_table = "cj_service_candidate",
 *   handlers = {
 *     "storage" = "Drupal\cj_crm\Entity\ServiceCandidateStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\cj_crm\Entity\ServiceCandidateAccessControlHandler",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   admin_permission = "administer candidates"
 * )
 *
 * @package Drupal\communication\Entity
 */
class ServiceCandidate extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['service'] = BaseFieldDefinition::create('service_reference')
      ->setLabel(t('Service'))
      ->setSetting('target_type', 'service')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['candidate'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Candidate'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_context',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
