<?php

namespace Drupal\cj;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\contacts_jobs\CacheabilityHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Utility\QueryHelperInterface;

/**
 * Helper for cacheable job queries.
 */
class JobQueries {

  /**
   * The cache key prefix.
   */
  const CACHE_KEY_PREFIX = 'cj.job_queries:';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Search API query helper.
   *
   * @var \Drupal\search_api\Utility\QueryHelperInterface
   */
  protected $queryHelper;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Static cache of the expiration time.
   *
   * @var int
   */
  protected $expires;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The job cacheability helper.
   *
   * @var \Drupal\contacts_jobs\CacheabilityHelper
   */
  protected $cacheabilityHelper;

  /**
   * Constructs a JobQueries service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\search_api\Utility\QueryHelperInterface $query_helper
   *   The search_api.query_helper service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\contacts_jobs\CacheabilityHelper $cacheability_helper
   *   The job cacheability helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryHelperInterface $query_helper, CacheBackendInterface $cache, TimeInterface $time, CacheabilityHelper $cacheability_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryHelper = $query_helper;
    $this->cache = $cache;
    $this->time = $time;
    $this->cacheabilityHelper = $cacheability_helper;
  }

  /**
   * Get the number of currently active jobs.
   *
   * @return int
   *   The number of active jobs.
   */
  public function getActiveCount(): int {
    $cache = $this->getCache('active_count');

    if (!$cache) {
      try {
        $query = $this->getBaseQuery()
          ->range(0, 0);
        $count = $query->execute()->getResultCount();
        $this->setCache('active_count', $count);
      }
      catch (\Exception $e) {
        // SOLR connection error.
        $count = 0;
      }
    }
    else {
      $count = $cache->data;
    }

    return (int) $count;
  }

  /**
   * Get the cached value for a key.
   *
   * @param string $key
   *   The cache key. Will be prefixed so only needs to be unique within job
   *   queries.
   *
   * @return object|null
   *   The cached value or NULL if not available.
   */
  protected function getCache(string $key) {
    return $this->cache->get(static::CACHE_KEY_PREFIX . $key);
  }

  /**
   * Set the cached value for a key.
   *
   * @param string $key
   *   The cache key. Will be prefixed so only needs to be unique within job
   *   queries.
   * @param mixed $value
   *   The value to cache.
   */
  protected function setCache(string $key, $value): void {
    if ($this->expires === NULL) {
      $this->expires = $this->cacheabilityHelper->getNextTransition();
    }

    $this->cache->set(
      static::CACHE_KEY_PREFIX . $key,
      $value,
      $this->expires,
      ['contacts_job_list'],
    );
  }

  /**
   * Get the base query for jobs.
   *
   * @param bool $published
   *   Whether only published jobs should be included.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The query.
   */
  public function getBaseQuery(bool $published = TRUE): QueryInterface {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->load('contacts_job_index');
    $query = $this->queryHelper->createQuery($index);

    if ($published) {
      $now = $this->time->getRequestTime();

      // Publish start in the past.
      $query->addCondition('publish_start', $now, '<=');

      // Publish end and closing in the future.
      $query->addCondition('closing', $now, '>');
      $query->addCondition('publish_end', $now, '>');
    }

    return $query;
  }

}
