<?php

use Drupal\Core\Url;

/**
 * Implements hook_xmlsitemap_link_alter().
 */
function job_board_xmlsitemap_link_alter(&$link, $context = []) {
  if ($link['type'] != 'user') {
    return;
  }

  /** @var \Drupal\user\UserInterface $user */
  $user = $context['user'];
  if ($user->hasRole('employer')) {
    $link['loc'] = Url::fromRoute('job_board.employer', ['user' => $user->id()])->toString();
  }
}
