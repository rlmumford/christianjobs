<?php

namespace Drupal\contacts_jobs_dashboard;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for the Jobboard Dashboard module.
 */
class ContactsJobsDashboardServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (!isset($modules['social_auth'])) {
      $container->removeDefinition('contacts_jobs_dashboard.social_auth_subscriber');
    }
  }

}
