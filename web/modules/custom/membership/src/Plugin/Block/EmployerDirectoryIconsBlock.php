<?php

namespace Drupal\cj_membership\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class EmployerDirectoryIconsBlock
 *
 * @Block(
 *   id = "employer_directory_icons_block",
 *   admin_label = @Translation("Employer Directory Icons"),
 *   context = {
 *     "employer" = @ContextDefinition("entity:user", label = @Translation("Employer"))
 *   }
 * )
 *
 * @package Drupal\cj_membership\Plugin\Block
 */
class EmployerDirectoryIconsBlock extends BlockBase {

  /**
   * @inheritDoc
   */
  public function build() {
    /** @var \Drupal\user\UserInterface $employer */
    $employer = $this->getContextValue('employer');

    if (!$employer->hasRole('employer')) {
      return [];
    }

    $paid_q = \Drupal::entityTypeManager()->getStorage('job_role')->getQuery();
    $paid_q->condition('organisation', $employer->id());
    $paid_q->condition('publish_date', (new DrupalDateTime())->format('Y-m-d'), '<=');
    $paid_q->condition('end_date', (new DrupalDateTime())->format('Y-m-d'), '>=');
    $paid_q->condition('paid', 1);
    $paid_count = $paid_q->count()->execute();

    $vol_q = \Drupal::entityTypeManager()->getStorage('volunteer_role')->getQuery();
    $vol_q->condition('organisation', $employer->id());
    $vol_count = $vol_q->count()->execute();

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['employer-directory-icons'],
      ],
    ];

    $build['paid_count']['#markup'] = "<span class=\"directory-paid-role-count directory-icon\"><span class=\"material-icons\">work</span>{$paid_count}</span>";
    $build['vol_count']['#markup'] = "<span class=\"directory-vol-role-count directory-icon\"><span class=\"material-icons\">emoji_people</span>{$vol_count}</span>";

    $query = \Drupal::database()->select('flag_counts', 'fc');
    $query->condition('fc.flag_id', 'employer_like');
    $query->condition('fc.entity_type', 'user');
    $query->condition('fc.entity_id', $employer->id());
    $query->addField('fc', 'count', 'count');
    $like_count = $query->execute()->fetchField();

    /** @var \Drupal\flag\FlagLinkBuilderInterface $link_builder */
    $link_builder = \Drupal::service('flag.link_builder');
    $build['like_count'] = $link_builder->build(
      'user',
      $employer->id(),
      'employer_like'
    );
    $build['like_count']['#attributes']['title'] = !empty($build['like_count']['#title']) ? $build['like_count']['#title'] : new TranslatableMarkup('Likes');
    $build['like_count']['#title'] = isset($like_count) ? $like_count : 0;

    if (empty($build['like_count']['#theme'])) {
      $build['like_count']['#markup'] = "<span class=\"directory-like-count directory-icon\">".(isset($like_count) ? $like_count : 0)."</span>";
    }

    return $build;
  }
}
