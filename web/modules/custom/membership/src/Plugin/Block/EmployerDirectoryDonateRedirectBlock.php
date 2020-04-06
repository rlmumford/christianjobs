<?php

namespace Drupal\cj_membership\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Class EmployerDirectoryIconsBlock
 *
 * @Block(
 *   id = "employer_directory_donate_redirect",
 *   admin_label = @Translation("Employer Directory Donate Link"),
 *   context = {
 *     "employer" = @ContextDefinition("entity:user", label = @Translation("Employer"))
 *   }
 * )
 *
 * @package Drupal\cj_membership\Plugin\Block
 */
class EmployerDirectoryDonateRedirectBlock extends BlockBase {

  /**
   * @inheritDoc
   */
  public function build() {
    /** @var \Drupal\user\UserInterface $employer */
    $employer = $this->getContextValue('employer');

    if (!$employer->hasRole('employer')) {
      return [];
    }

    /** @var \Drupal\profile\Entity\Profile $profile */
    $profile = \Drupal::entityTypeManager()->getStorage('profile')
      ->loadDefaultByUser($employer, 'employer');

    if ($profile->employer_on_directory->isEmpty() || !$profile->employer_on_directory->value) {
      return [];
    }

    if ($profile->employer_donate_link->isEmpty()) {
      return [];
    }

    $build = [
      '#type' => 'link',
      '#title' => new TranslatableMarkup('Donate'),
      '#url' => Url::fromRoute('member.donate_redirect', ['user' => $employer->id()]),
      '#attributes' => [
        'rel' => 'nofollow',
        'class' => [
          'button',
          'donate-button',
        ],
      ],
    ];

    return $build;
  }
}
