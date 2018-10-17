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

    // Industry field.
    $data[$table_name]['industry']['filter']['id'] = 'taxonomy_index_tid';
    $data[$table_name]['industry']['filter']['entity_type'] = 'job_role';
    $data[$table_name]['industry']['filter']['field_name'] = 'industry';

    // List fields.
    foreach (['hours', 'compensation'] as $field_name) {
      $data[$table_name][$field_name]['filter'] = [
        'id' => 'list_field',
        'entity_type' => 'job_role',
        'field_name' => $field_name,
      ] + $data[$table_name][$field_name]['filter'];
    }

    // Address fields.
    foreach (['location', 'contact_address'] as $field_name) {
      // Add the custom country_code filter.
      $data[$table_name][$field_name . '__country_code']['filter'] = [
        'id' => 'country_code',
        'entity_type' => 'job_role',
        'field_name' => $field_name,
      ] + $data[$table_name][$field_name.'__country_code']['filter'];
      // Add the custom administrative_area filter.
      $data[$table_name][$field_name . '__administrative_area']['filter'] = [
        'id' => 'administrative_area',
        'entity_type' => 'job_role',
        'field_name' => $field_name,
      ] + $data[$table_name][$field_name.'__administrative_area']['filter'];
    }

    // Keywords field
    $data[$table_name.'__keywords']['keywords_target_id']['filter'] = [
      'id' => 'taxonomy_index_tid',
      'entity_type' => 'job_role',
      'field_name' => 'keywords',
    ] + $data[$table_name.'__keywords']['keywords_target_id']['filter'];
  }
}
