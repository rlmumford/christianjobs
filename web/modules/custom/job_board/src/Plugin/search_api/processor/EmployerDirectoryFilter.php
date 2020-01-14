<?php

namespace Drupal\job_board\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\UserInterface;

/**
 * Filters out users based on whether they should be on the directory
 *
 * @SearchApiProcessor(
 *   id = "employer_on_directory",
 *   label = @Translation("Employer Directory"),
 *   description = @Translation("Filters out users based on whether they are on the directory"),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class EmployerDirectoryFilter extends ProcessorPluginBase {

  /**
   * Can only be enabled for an index that indexes the user/profile entity.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'user' || $datasource->getEntityTypeId() == 'profile') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $profile = $item->getOriginalObject()->getValue();
      if ($profile instanceof UserInterface) {
        $profile = $profile_storage->loadByUser($profile, 'employer');
      }

      if (!$profile || ($profile->bundle() != 'employer')) {
        unset($items[$item_id]);
        continue;
      }

      if (!$profile->employer_on_directory->value) {
        unset($items[$item_id]);
      }
    }
  }
}
