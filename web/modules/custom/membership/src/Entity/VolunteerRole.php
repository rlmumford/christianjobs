<?php

namespace Drupal\cj_membership\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\job_role\Entity\JobRole;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Class VolunteerRole
 *
 * @ContentEntityType(
 *   id = "volunteer_role",
 *   label = @Translation("Volunteer Role"),
 *   label_singular = @Translation("volunteer role"),
 *   label_plural = @Translation("volunteer roles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count volunteer role",
 *     plural = "@count volunteer roless"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\cj_membership\Entity\VolunteerRoleAccessControlHandler",
 *     "permission_provider" = "Drupal\cj_membership\Entity\VolunteerRolePermissionProvider",
 *     "form" = {
 *       "default" = "Drupal\cj_membership\Form\VolunteerRoleForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "volunteer_role",
 *   revision_table = "volunteer_role_revision",
 *   admin_permission = "administer volunteer roles",
 *   links = {
 *     "canonical" = "/volunteer/{volunteer_role}",
 *     "edit-form" = "/volunteer/{volunteer_role}/edit",
 *     "delete-form" = "/volunteer/{volunteer_role}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "owner"
 *   }
 * )
 *
 * @package Drupal\cj_membership\Entity
 */
class VolunteerRole extends JobRole implements EntityOwnerInterface {
  use EntityOwnerTrait;

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    unset($fields['salary']);
    $fields['description_summary'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Summary'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'weight' => -5,
        'type' => 'text_textarea',
      ]);

    $fields['keywords'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'keywords' => 'keywords',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'keywords',
      ])
      ->setLabel(t('Keywords'))
      ->setDescription(t('Select up to 10 keywords to describe this job.'))
      ->setCardinality(10)
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => -6,
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['industry'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'industries' => 'industries',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'industries',
      ])
      ->setLabel(t('Category'))
      ->setDescription(t('Select an category.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => -7,
        'type' => 'entity_reference_autocomplete',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['location_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Location Type'))
      ->setSetting('allowed_values', [
        'home' => new TranslatableMarkup('Home based'),
        'remote' => new TranslatableMarkup('Remote working'),
        'office' => new TranslatableMarkup('Office based'),
        'location' => new TranslatableMarkup('On location'),
      ])
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
    $fields['location_geo'] = BaseFieldDefinition::create('geofield')
      ->setLabel('Location Geo')
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
    $fields['location_tree'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Location Tree')
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'locations' => 'locations',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => 'locations',
      ])
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);
    $fields['location'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Location'))
      ->setSetting('field_overrides', [
        'givenName' => ['override' => 'hidden'],
        'additionalName' => ['override' => 'hidden'],
        'familyName' => ['override' => 'hidden'],
        'organization' => ['override' => 'hidden'],
        'addressLine1' => ['override' => 'hidden'],
        'addressLine2' => ['override' => 'hidden'],
        'postalCode' => ['override' => 'hidden'],
        'sortingCode' => ['override' => 'hidden'],
        'dependentLocality' => ['override' => 'hidden'],
      ])
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'address_default',
      ]);

    $fields['contact_phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Contact Telephone'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback('job_board_job_role_contact_default_value')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'telephone_default',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['contact_address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Contact Address'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback('job_board_job_role_contact_default_value')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'address_default',
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['contact_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Contact E-mail Address'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback('job_board_job_role_contact_default_value')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Geocode the location.
   */
  protected function geocodeLocation() {
    /** @var \Drupal\geocoder_field\PreprocessorPluginManager $preprocessor_manager */
    $preprocessor_manager = \Drupal::service('plugin.manager.geocoder.preprocessor');
    /** @var \Drupal\geocoder\DumperPluginManager $dumper_manager */
    $dumper_manager = \Drupal::service('plugin.manager.geocoder.dumper');

    $address = $this->location;
    if ($this->original) {
      $original_address = $this->original->location;
    }

    // First we need to Pre-process field.
    // Note: in case of Address module integration this creates the
    // value as formatted address.
    $preprocessor_manager->preprocess($address);

    // Skip any action if:
    // geofield has value and remote field value has not changed.
    if (isset($original_address) && !$this->get('location_geo')->isEmpty() && $address->getValue() == $original_address->getValue()) {
      return;
    }

    $dumper = $dumper_manager->createInstance('geojson');
    $result = [];

    foreach ($address->getValue() as $delta => $value) {
      if ($address->getFieldDefinition()->getType() == 'address_country') {
        $value['value'] = CountryManager::getStandardList()[$value['value']];
      }

      $address_collection = isset($value['value']) ? \Drupal::service('geocoder')->geocode($value['value'], ['googlemaps', 'googlemaps_business']) : NULL;
      if ($address_collection) {
        $result[$delta] = $dumper->dump($address_collection->first());

        // We can't use DumperPluginManager::fixDumperFieldIncompatibility
        // because we do not have a FieldConfigInterface.
        // Fix not UTF-8 encoded result strings.
        // https://stackoverflow.com/questions/6723562/how-to-detect-malformed-utf-8-string-in-php
        if (is_string($result[$delta])) {
          if (!preg_match('//u', $result[$delta])) {
            $result[$delta] = utf8_encode($result[$delta]);
          }
        }
      }
    }

    $this->set('location_geo', $result);

    $terms = [];
    $data = json_decode($this->location_geo->value);
    if (!$data || !isset($data->properties->adminLevels)) {
      return;
    }

    // First get the tem for the country.
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    if (!($term = reset($term_storage->loadByProperties(['vid' => 'locations', 'name' => $data->properties->country])))) {
      $term = $term_storage->create([
        'vid' => 'locations',
        'name' => $data->properties->country,
      ]);
      $term->save();
    }
    $terms[] = $term;

    foreach ($data->properties->adminLevels as $level) {
      if (!($next_term = reset($term_storage->loadByProperties(['vid' => 'locations', 'name' => $level->name, 'parent' => $term->id()])))) {
        $next_term = $term_storage->create([
          'vid' => 'locations',
          'name' => $level->name,
          'parent' => $term->id(),
        ]);
        $next_term->save();
      }
      $terms[] = $term = $next_term;
    }

    $this->location_tree = $terms;
  }

  /**
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *
   * @throws \Exception
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->geocodeLocation();
  }


}
