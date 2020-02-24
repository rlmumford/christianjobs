<?php

/**
 * Implements hook_views_data_alter().
 */
function cj_membership_views_data_alter(array &$data) {
  foreach (['volunteer_role', 'volunteer_role_revision'] as $table_name) {
    // Industry field.
    $data[$table_name]['industry']['filter']['id'] = 'taxonomy_index_tid';
    $data[$table_name]['industry']['filter']['entity_type'] = 'volunteer_role';
    $data[$table_name]['industry']['filter']['field_name'] = 'industry';

    // Organisation field.
    $data[$table_name]['organisation']['filter']['id'] = 'entity_reference';
    $data[$table_name]['organisation']['filter']['entity_type'] = 'volunteer_role';
    $data[$table_name]['organisation']['filter']['field_name'] = 'organisation';

    // Location field
    $data[$table_name]['location_geo__proximity'] = [
      'title' => 'Location Geo Proximity',
      'help' => 'Geometric Location Proximity',
      'field' => [
        'id' => 'geofield_proximity_field',
        'real field' => 'location_geo',
        'float' => TRUE,
        'click sortable' => TRUE,
        'additional fields' => [
          'latitude' => [
            'field' => 'location_geo__lat',
          ],
          'longitude' => [
            'field' => 'location_geo__lon',
          ],
        ],
      ],
      'filter' => [
        'id' => 'geofield_proximity_filter',
        'entity_type' => 'volunteer_role',
        'field_name' => 'location_geo',
        'real field' => 'location_geo_',
      ],
    ];

    // Address fields.
    foreach (['location', 'contact_address'] as $field_name) {
      // Add the custom country_code filter.
      $data[$table_name][$field_name . '__country_code']['filter'] = [
        'id' => 'country_code',
        'entity_type' => 'volunteer_role',
        'field_name' => $field_name,
      ] + $data[$table_name][$field_name.'__country_code']['filter'];
      // Add the custom administrative_area filter.
      $data[$table_name][$field_name . '__administrative_area']['filter'] = [
        'id' => 'administrative_area',
        'entity_type' => 'volunteer_role',
        'field_name' => $field_name,
      ] + $data[$table_name][$field_name.'__administrative_area']['filter'];
    }

    // Keywords field
    $data[$table_name.'__keywords']['keywords_target_id']['filter'] = [
      'id' => 'taxonomy_index_tid',
      'entity_type' => 'volunteer_role',
      'field_name' => 'keywords',
    ] + $data[$table_name.'__keywords']['keywords_target_id']['filter'];

    // location_tree field
    $data[$table_name.'__location_tree']['location_tree_target_id']['filter'] = [
      'id' => 'cshs_taxonomy_index_tid',
      'entity_type' => 'volunteer_role',
      'field_name' => 'location_tree',
    ] + $data[$table_name.'__location_tree']['location_tree_target_id']['filter'];
  }
}
