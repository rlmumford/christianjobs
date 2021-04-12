<?php

/**
 * @file
 * Views hooks for Jobboard dashboard.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_views_data_alter().
 */
function contacts_jobs_dashboard_views_data_alter(array &$data) {
  $data['users_field_data']['contacts_jobs_dashboard_org_manage'] = [
    'field' => [
      'title' => new TranslatableMarkup('Link to manage organisation team'),
      'help' => new TranslatableMarkup('Provide a manage link for the organisation.'),
      'id' => 'contacts_jobs_dashboard_org_manage',
    ],
  ];
  $data['group__contacts_org']['contacts_jobs_dashboard_org_leave'] = [
    'field' => [
      'title' => new TranslatableMarkup('Link to leave group'),
      'help' => new TranslatableMarkup('Provide a leave link for the group with a return destination.'),
      'id' => 'contacts_jobs_dashboard_org_leave',
    ],
  ];
  $data['contacts_job']['submitted_applications_count'] = [
    'title' => new TranslatableMarkup('Number of Submitted Applications'),
    'real field' => 'id',
    'field' => [
      'id' => 'contacts_jobs_dashboard_count_submitted_applications',
    ],
  ];
}
