<?php

namespace Drupal\job_board\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber
 *
 * @package Drupal\job_board\EventSubscriber
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('user.register');
    $route->setOption('no_cache', TRUE);
  }

}
