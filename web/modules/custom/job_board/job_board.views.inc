<?php

/**
 * Implements hook_views_data_alter().
 */
function job_board_views_data_alter(array &$data) {
  foreach (['job_role', 'job_role_revision'] as $table_name) {
    foreach (['publish_date', 'end_date'] as $field_name) {
      $data[$table_name][$field_name]['filter']['id'] = 'datetime';
      $data[$table_name][$field_name]['filter']['entity_type'] = 'job_role';
      $data[$table_name][$field_name]['filter']['field_name'] = $field_name;
      $data[$table_name][$field_name]['sort']['id'] = 'datetime';
      $data[$table_name][$field_name]['sort']['entity_type'] = 'job_role';
      $data[$table_name][$field_name]['sort']['field_name'] = $field_name;
      $data[$table_name][$field_name]['argument']['id'] = 'datetime';
      $data[$table_name][$field_name]['argument']['entity_type'] = 'job_role';
      $data[$table_name][$field_name]['argument']['field_name'] = $field_name;
    }
  }

  foreach (['job_role__featured_dates', 'job_role_revision__featured_dates'] as $table_name) {
    $data[$table_name]['featured_dates_value']['filter']['id'] = 'datetime';
    $data[$table_name]['featured_dates_value']['filter']['entity_type'] = 'job_role';
    $data[$table_name]['featured_dates_value']['filter']['field_name'] = 'featured_dates';
    $data[$table_name]['featured_dates_value']['argument']['id'] = 'datetime';
    $data[$table_name]['featured_dates_value']['argument']['entity_type'] = 'job_role';
    $data[$table_name]['featured_dates_value']['argument']['field_name'] = 'featured_dates';
    $data[$table_name]['featured_dates_value']['sort']['id'] = 'datetime';
    $data[$table_name]['featured_dates_value']['sort']['entity_type'] = 'job_role';
    $data[$table_name]['featured_dates_value']['sort']['field_name'] = 'featured_dates';
  }
}
