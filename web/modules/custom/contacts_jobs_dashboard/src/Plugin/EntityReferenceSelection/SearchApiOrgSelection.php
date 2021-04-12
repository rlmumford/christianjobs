<?php

namespace Drupal\contacts_jobs_dashboard\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\contacts\Plugin\EntityReferenceSelection\SearchApiSelection;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Customised entity reference selection for recruiter reg form.
 *
 * Includes the website and address in the label.
 *
 * @EntityReferenceSelection(
 *   id = "search_api:organisation",
 *   label = @Translation("Organisation selection"),
 *   group = "search_api",
 *   weight = 2
 * )
 */
class SearchApiOrgSelection extends SearchApiSelection {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->requestStack = $container->get('request_stack');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->getQuery($match, $match_operator);
    if ($limit) {
      $query->range(0, $limit);
    }

    $results = $query->execute();

    $create_options = [];
    if (!empty($this->configuration['create_option']) && $match) {
      // Get the entered text from the request to avoid capitalization issues.
      $name = $this->requestStack->getCurrentRequest()->query->get('q');
      $create_options['new']['new'] = "Create '{$name}'";
    }

    if ($results->getResultCount() == 0) {
      return $create_options;
    }

    foreach ($results->getResultItems() as $result) {
      try {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $result->getOriginalObject()->getValue();
        $label = $entity->label();

        if ($entity instanceof ContentEntityBase && $entity->hasField('profile_crm_org')) {
          if ($website = $entity->profile_crm_org->entity->website->value) {
            $label .= ' - ' . $website;
          }
          if ($hq = $entity->profile_crm_org->entity->headquarters->entity) {
            /** @var \Drupal\contacts_jobs_place\Entity\Place $hq */
            if (!$hq->address->isEmpty()) {
              $city = $hq->address->locality;
              $country = $hq->address->country_code;
              $label .= " ($city, $country)";
            }
          }
        }

        $options[$entity->bundle()][$entity->id()] = Html::escape($label);
      }
      catch (SearchApiException $exception) {
        // Skip over this item.
      }
    }

    return $options + $create_options;
  }

}
