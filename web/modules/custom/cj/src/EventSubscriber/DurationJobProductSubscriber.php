<?php

namespace Drupal\cj\EventSubscriber;

use Drupal\contacts_jobs_commerce\Event\JobProductVariationEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to select the correct job posting product.
 *
 * Selects the job posting production on the basis of the duration requested by
 * the customer.
 */
class DurationJobProductSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DurationJobProductSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Allow products with the right duration.
   *
   * @param \Drupal\contacts_jobs_commerce\Event\JobProductVariationEvent $event
   *   The product variation selection event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function allowOnlyDurationProduct(JobProductVariationEvent $event) {
    // @todo: Select this from information on the job.
    $duration = 'P30D';

    $storage = $this->entityTypeManager->getStorage('commerce_product');
    $ids = $storage->getQuery()
      ->condition('type', 'contacts_job_posting')
      ->condition('cj_post_duration', $duration)
      ->execute();

    if (!empty($ids)) {
      $event->allowProducts($ids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Get the default job, running last.
      JobProductVariationEvent::NAME => ['allowOnlyDurationProduct', 100],
    ];
  }
}
