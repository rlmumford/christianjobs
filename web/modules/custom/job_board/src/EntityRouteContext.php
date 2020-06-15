<?php

namespace Drupal\job_board;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_route_context\ContextProvider\EntityRouteContext as BaseEntityRouteContext;

class EntityRouteContext extends BaseEntityRouteContext {

  protected function getRouteMatchEntity(RouteMatchInterface $routeMatch): ?EntityInterface {
    $entity = parent::getRouteMatchEntity($routeMatch);

    if ($entity) {
      return $entity;
    }

    // @todo: Try and support views generally.
    if ($this->routeMatch->getRouteName() === 'view.job_board__recruiter_jobs.page') {
      return $this->entityTypeManager->getStorage('organization')->load(
        $this->routeMatch->getParameter('organization')
      );
    }

    return NULL;
  }

}
