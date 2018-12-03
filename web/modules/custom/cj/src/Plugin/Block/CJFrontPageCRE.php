<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays page contemt.
 *
 * @Block(
 *   id = "cj_front_page_cre",
 *   admin_label = @Translation("Christian Jobs Front Page CRE"),
 * )
 */
class CJFrontPageCRE extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Creates a CJHeaderBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *  The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
  }

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row-wrapper', 'cre', 'section-wrapper', 'section-wrapper--dark-blue'],
      ],
      'row' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['row', 'section'],
        ],
        'logo' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [ 'col-xs-12', 'col-md-4', 'col-xs', 'col-md' ],
          ],
          'logo' => [
            '#theme' => 'image',
            '#uri' => drupal_get_path('module', 'cj').'/assets/CRE-logo.png',
            '#alt' => new TranslatableMarkup('Christian Resource Exhibition'),
            '#title' => 'Christian Resource Exhibition',
          ],
        ],
        'content' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['col-xs', 'col-xs-12', 'col-md', 'col-md-8', 'cre-text'],
          ],
          'content' => [
            '#markup' => '<p class="hero--text">Meet us at CRE North, 13-14th March 2019</p><p>Planning to attend? Contact us for a free entry code.</p>'
          ],
        ]
      ],
    ];

    return $build;
  }

}
