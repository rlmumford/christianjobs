<?php

namespace Drupal\job_board;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class JobBoardServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($definition = $container->getDefinition('entity_route_context.entity_route_context')) {
      $definition->setClass('\Drupal\job_board\EntityRouteContext');
    }
  }

}
