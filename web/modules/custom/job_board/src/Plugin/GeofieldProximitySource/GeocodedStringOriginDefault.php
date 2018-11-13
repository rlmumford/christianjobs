<?php

namespace Drupal\job_board\Plugin\GeofieldProximitySource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Plugin\GeofieldProximitySourceBase;

/**
 * Defines 'Geofield Geocoded Origin' plugin.
 *
 * @GeofieldProximitySource(
 *   id = "job_board_geocoded_string_origin",
 *   label = @Translation("Text"),
 *   context = {},
 * )
 */
class GeocodedStringOriginDefault extends GeofieldProximitySourceBase {

  /**
   * The origin point to measure proximity from.
   *
   * @var array
   */
  protected $origin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->origin['lat'] = isset($configuration['origin']) && is_numeric($configuration['origin']['lat']) ? $configuration['origin']['lat'] : '';
    $this->origin['lon'] = isset($configuration['origin']) && is_numeric($configuration['origin']['lon']) ? $configuration['origin']['lon'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(array &$form, FormStateInterface $form_state, array $options_parents, $is_exposed = FALSE) {

    $form["origin"] = [
      '#title' => t('Origin'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['origin']['text'],
      '#element_validate' => [[static::class, 'originTextElementValidate']],
    ];

    if ($this->viewHandler->configuration['id'] == 'geofield_proximity_filter' && !$is_exposed) {
      $form['origin_unexposed'] = [
        '#type' => 'checkbox',
        '#title' => t('Hide the Origin Input elements from the Exposed Form'),
        '#default_value' => isset($this->configuration['origin_unexposed']) ? $this->configuration['origin_unexposed'] : FALSE,
        '#states' => [
          'visible' => [
            ':input[name="options[expose_button][checkbox][checkbox]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['origin_unexposed_summary'] = [
        '#type' => 'checkbox',
        '#title' => t('Show (anyway) the Origin coordinates as summary in the Exposed Form'),
        '#default_value' => isset($this->configuration['origin_unexposed_summary']) ? $this->configuration['origin_unexposed_summary'] : TRUE,
        '#states' => [
          'visible' => [
            ':input[name="options[source_configuration][origin_unexposed]"]' => ['checked' => TRUE],
          ],
        ],
      ];

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function originTextElementValidate(array &$element, FormStateInterface $form_state, array $complete_form) {
    $values = $form_state->getValue($element['#parents']);

    if (is_string($values) && !empty($values)) {
      $text = $values;
      /** @var \Geocoder\Model\AddressCollection $address_collection */
      $address_collection = \Drupal::service('geocoder')->geocode($text, [
        'googlemaps',
        'googlemaps_business',
      ]);

      $values = [
        'text' => $text,
        'lat' => $address_collection->first()->getLatitude(),
        'lon' => $address_collection->first()->getLongitude(),
      ];
    }
    else {
      $values = [];
    }

    $form_state->setValueForElement($element, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getOrigin() {
    return !empty($this->origin['lat']) && !empty($this->origin['lon']) ? $this->origin : NULL;
  }
}
