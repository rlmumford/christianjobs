<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays page header.
 *
 * @Block(
 *   id = "cj_header_block",
 *   admin_label = @Translation("Christian Jobs Header Block"),
 * )
 */
class CJHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Creates a CJHeaderBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'tagline' => NULL,
      'find_cta_text' => NULL,
      'find_cta_path' => NULL,
      'post_cta_text' => NULL,
      'post_cta_path' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['tagline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tagline'),
      '#default_value' => $this->configuration['tagline'],
    ];

    $form['ctas'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Call to Action Buttons'),
    ];
    $form['ctas']['find_cta_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Find a Job Button Text'),
      '#default_value' => $this->configuration['find_cta_text'],
    ];
    $form['ctas']['find_cta_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Find a Job Button Path'),
      '#default_value' => $this->configuration['find_cta_path'],
    ];
    $form['ctas']['post_cta_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Post a Job Button Text'),
      '#default_value' => $this->configuration['post_cta_text'],
    ];
    $form['ctas']['post_cta_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Post a Job Button Path'),
      '#default_value' => $this->configuration['post_cta_path'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['tagline'] = $form_state->getValue('tagline');

    $values = $form_state->getValue('ctas');
    $this->configuration['find_cta_text'] = $values['find_cta_text'];
    $this->configuration['find_cta_path'] = $values['find_cta_path'];
    $this->configuration['post_cta_text'] = $values['post_cta_text'];
    $this->configuration['post_cta_path'] = $values['post_cta_path'];
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
    $build = [];
    $build['logo'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [ 'header-front' ]
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => $this->t('Christian Jobs'),
      ],
      'tagline' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['tagline']
        ],
        '#value' => $this->configuration['tagline'],
      ],
    ];
    $build['ctas'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [ 'header-front-ctas' ],
      ],
      'find' => [
        '#type' => 'link',
        '#title' => $this->configuration['find_cta_text'],
        '#url' => Url::fromUri('internal:/'.$this->configuration['find_cta_path']),
        '#attributes' => [
          'class' => 'button button-cta button-blue',
        ],
      ],
      'post' => [
        '#type' => 'link',
        '#title' => $this->configuration['post_cta_text'],
        '#url' => Url::fromUri('internal:/'.$this->configuration['post_cta_path']),
        '#attributes' => [
          'class' => 'button button-cta button-orange',
        ],
      ],
    ];

    if (!$this->currentUser->isAuthenticated()) {
      $build['ctas']['post']['#url'] = Url::fromRoute('user.register', [], ['query' => ['register' => 'recruiter']]);
    }
    elseif ($this->currentUser->hasPermission('post new jobs')) {
      $build['ctas']['post']['#url'] = Url::fromRoute('entity.contacts_jobs.post_form', ['contacts_job_type' => 'job']);
    }
    else {
      $build['ctas']['post']['#access'] = FALSE;
    }

    $build['#cache']['contexts'] = ['user.roles'];

    return $build;
  }

}
