<?php

namespace Drupal\job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\job_role\Entity\JobRole;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays page contemt.
 *
 * @Block(
 *   id = "front_page_boosted_jobs",
 *   admin_label = @Translation("Front Page Boosted Jobs"),
 * )
 */
class FrontPageBoostedJobs extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager')->getStorage('job_role'));
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
    $current_date = new DrupalDateTime();
    $query = $this->storage->getQuery();
    $query->condition('boost_start_date', $current_date->format('Y-m-d'), '<=' );
    $query->condition('boost_end_date', $current_date->format('Y-m-d'), '>=' );
    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row-wrapper', 'partners', 'section-wrapper'],
      ],
      '#cache' => [
        'tags' => ['boosted_jobs'],
        'max-age' => 60*60*24,
      ],
      'row' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['row', 'section'],
        ],
        'title' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['col-xs-12'],
          ],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#attributes' => [
              'class' => [ 'section-header' ]
            ],
            '#value' => new TranslatableMarkup('Featured Jobs'),
          ],
        ],
      ],
    ];

    foreach ($this->storage->loadMultiple($ids) as $job) {
      $build['row'][$job->id()] = $this->buildJobCard($job);
    }

    return $build;
  }

  /**
   * Build a partner card.
   */
  protected function buildJobCard(JobRole $job) {
    $employer = $job->organisation->entity;
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    $profile = $profile_storage->loadDefaultByUser($employer, 'employer');

    $build = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['col-xs-12', 'col-s-6', 'col-md-3'],
      ],
      'content' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'class' => ['card', 'job-card'],
          'href' => $job->toUrl()->toString(),
          'target' => '_blank',
        ],
      ],
    ];

    if (!$profile->logo->isEmpty()) {
      $build['content']['logo'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['card-item', 'card-media', 'media-cover'],
        ],
        'image' => $profile->logo->view([
          'type' => 'image',
          'label' => 'hidden',
        ]),
      ];
    }

    $build['content']['title'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['card-item', 'card-title'],
      ],
      'content' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $job->label(),
      ],
      'subtitle' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['subtitle'],
        ],
        '#value' => $profile->employer_name->value,
      ],
    ];
    $build['content']['description'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['card-item', 'card-text'],
      ],
      'category' => $job->industry->view([
        'type' => 'entity_reference_label',
        'label' => 'inline',
        'settings' => [
          'link' => FALSE,
        ],
      ]),
      'salary' => $job->salary->view([
        'label' => 'inline',
        'type' => 'range_default',
        'settings' => [
          'thousand_separator' => ',',
          'from_prefix_suffix' => TRUE,
          'to_prefix_suffix' => TRUE,
        ],
      ]),
    ];
    $build['content']['cta'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['card-item', 'card-text', 'text-align-right'],
      ],
      'content' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['material-icons', 'material-icons-extended', 'parter-cta'],
        ],
        '#value' => 'arrow_forward',
      ],
    ];

    return $build;
  }

}
