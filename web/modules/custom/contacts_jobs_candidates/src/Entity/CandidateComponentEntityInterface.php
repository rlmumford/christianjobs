<?php

namespace Drupal\contacts_jobs_candidates\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Candidate component entities.
 *
 * @ingroup contacts_jobs_apps
 */
interface CandidateComponentEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the component entity name.
   *
   * @return string
   *   Name of the Education qualification.
   */
  public function getName();

  /**
   * Sets the component entity name.
   *
   * @param string $name
   *   The component entity name.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The called component entity.
   */
  public function setName($name);

  /**
   * Gets the component entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the component entity.
   */
  public function getCreatedTime();

  /**
   * Sets the component entity creation timestamp.
   *
   * @param int $timestamp
   *   The component entity creation timestamp.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The called component entity.
   */
  public function setCreatedTime($timestamp);

}
