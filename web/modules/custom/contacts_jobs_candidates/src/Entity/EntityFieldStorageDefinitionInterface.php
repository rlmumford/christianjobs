<?php

namespace Drupal\contacts_jobs_candidates\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * An interface for entities with provide the bundle field storage information.
 */
interface EntityFieldStorageDefinitionInterface extends FieldableEntityInterface {

  /**
   * Get the field storage definitions for bundle fields.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Drupal\entity\BundleFieldDefinition[]
   *   The bundle field definitions.
   */
  public static function fieldStorageDefinitions(EntityTypeInterface $entity_type): array;

}
