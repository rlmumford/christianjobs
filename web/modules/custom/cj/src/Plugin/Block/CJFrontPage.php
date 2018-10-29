<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays page contemt.
 *
 * @Block(
 *   id = "cj_front_page",
 *   admin_label = @Translation("Christian Jobs Front Page"),
 * )
 */
class CJFrontPage extends BlockBase implements ContainerFactoryPluginInterface {

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
    $form['blurb'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Front Page Blurb'),
      '#default_value' => $this->configuration['blurb']['value'],
      '#format' => $this->configuration['blurb']['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['blurb'] = $form_state->getValue('blurb');
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
        'class' => ['row-wrapper'],
      ],
      'row' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['row'],
        ],
        'left' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['col-xs-12', 'col-md-6', 'col-left'],
          ],
          'content' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['col-left-content'],
            ],
            'scripture' => [
              '#type' => 'html_tag',
              '#attributes' => [
                'class' => ['scripture'],
              ],
              '#tag' => 'p',
              '#value' => 'Whatever you do, work at it with all your heart, as working for the Lord, not for human masters.'
            ],
            'reference' => [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['pull-bottom'],
              ],
              'content' => [
                '#type' => 'html_tag',
                '#tag' => 'span',
                '#attributes' => [
                  'class' => ['bible-ref'],
                ],
                '#value' => 'Colossians 3:23',
              ],
            ],
          ],
        ],
        'right' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['col-xs-12', 'col-md-6', 'col-right'],
          ],
          'content' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['col-right-content'],
            ],
            'content' => [
              '#type' => 'processed_text',
              '#text' => $this->configuration['blurb']['value'],
              '#format' => $this->configuration['blurb']['format'],
            ],
          ],
        ],
      ]
    ];

    return $build;
  }

}
