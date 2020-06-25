<?php

namespace Drupal\job_candidate\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\user\EntityOwnerInterface;

class ComputedCVInlineEntityReferenceFieldItemList extends EntityReferenceFieldItemList {
  use ComputedItemListTrait;

  /**
   * Computes the values for an item list.
   */
  protected function computeValue() {
    $profile = $this->getEntity();
    if (!($profile instanceof EntityOwnerInterface) || !($owner = $profile->getOwnerId())) {
      return;
    }

    $target_type = $this->getFieldDefinition()
      ->getFieldStorageDefinition()->getSetting('target_type');
    $target_storage = \Drupal::entityTypeManager()->getStorage($target_type);
    $target_type = \Drupal::entityTypeManager()->getDefinition($target_type);
    $query = $target_storage->getQuery()
      ->condition($target_type->getKey('owner'), $owner);

    $delta = 0;
    foreach ($query->execute() as $id) {
      $this->list[$delta] = $this->createItem($delta, $id);
      $delta++;
    }
  }
}
