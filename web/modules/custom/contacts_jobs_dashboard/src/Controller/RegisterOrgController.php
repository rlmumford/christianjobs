<?php

namespace Drupal\contacts_jobs_dashboard\Controller;

use Drupal\contacts_jobs_dashboard\Form\ExistingRecruiterOrgForm;
use Drupal\contacts_jobs_dashboard\Form\NewRecruiterOrgForm;
use Drupal\Core\Controller\ControllerBase;

/**
 * Register organisation controller.
 *
 * @package Drupal\contacts_jobs_dashboard\Controller
 */
class RegisterOrgController extends ControllerBase {

  /**
   * Register form.
   *
   * @return array
   *   Output.
   */
  public function register() {
    $build = [];

    $build['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Your Organisation Details'),
    ];

    $build['existing_org'] = $this->formBuilder()->getForm(ExistingRecruiterOrgForm::class);
    $build['new_org'] = $this->formBuilder()->getForm(NewRecruiterOrgForm::class);

    return $build;
  }

  /**
   * Redirects the user to their organisation dashboard.
   *
   * If the user doesn't have an organisation, they're redirected to the
   * organisation register page instead.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response.
   */
  public function viewOrRegister() {
    // Load the user (currentUser might not be an instance of User entity).
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    if ($item = $user->get('organisations')->first()) {
      /** @var \Drupal\group\GroupMembership $membership */
      $membership = $item->membership;
      $org_id = $membership->getGroup()->get('contacts_org')->target_id;

      return $this->redirect('contacts_user_dashboard.summary', [
        'user' => $org_id,
      ]);
    }
    // User not associated with an organisation. Send them to register page.
    return $this->redirect('contacts_jobs_dashboard.recruiter_organisation.register');
  }

}
