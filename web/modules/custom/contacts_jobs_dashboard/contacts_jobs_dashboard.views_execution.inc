<?php

/**
 * @file
 * Views execution hooks for Jobboard dashboard.
 */

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_alter().
 */
function contacts_jobs_dashboard_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  if ($view->id() === 'contacts_orgs_manage' && $view->current_display === 'member_indivs') {
    $table_queue = &$query->getTableQueue();
    // Filter the group roles join by delta to prevent duplicates.
    if (isset($table_queue['group_content__group_roles'])) {
      $table_queue['group_content__group_roles']['join']->extra[] = [
        'field' => 'delta',
        'value' => 0,
        'numeric' => TRUE,
      ];
    }
  }
}
