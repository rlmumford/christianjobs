<?php

namespace Drupal\job_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

class CandidateRegisterController extends ControllerBase {

  /**
   * Get the personal details for.
   *
   * @param \Drupal\user\UserInterface|NULL $user
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function personalDetailsForm(UserInterface $user = NULL) {
    $user = $user ?: $this->currentUser();

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager()
      ->getStorage('profile');

    $profile = $profile_storage->loadByUser($user, 'personal');
    if (!$profile) {
      $profile = $profile_storage->create([
        'type' => 'personal',
        'uid' => $user->id(),
      ]);
    }

    return $this->entityFormBuilder()->getForm(
      $profile,
      'register'
    );
  }

  /**
   * Get the cv/resume details for.
   *
   * @param \Drupal\user\UserInterface|NULL $user
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resumeDetailsForm(UserInterface $user = NULL) {
    $user = $user ?: $this->currentUser();

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager()
      ->getStorage('profile');

    $profile = $profile_storage->loadByUser($user, 'resume');
    if (!$profile) {
      $profile = $profile_storage->create([
        'type' => 'resume',
        'uid' => $user->id(),
      ]);
    }

    return $this->entityFormBuilder()->getForm(
      $profile,
      'register'
    );
  }

}
