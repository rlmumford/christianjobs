<?php

namespace Drupal\contacts_jobs_candidates;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of candidate component entities.
 *
 * @ingroup contacts_jobs_candidates
 */
class CandidateComponentEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\contacts_jobs_candidates\Entity\ProfessionalQualification $entity */
    $row['id'] = $entity->id();
    $row['title'] = $entity->toLink(NULL, 'edit-form');
    return $row + parent::buildRow($entity);
  }

}
