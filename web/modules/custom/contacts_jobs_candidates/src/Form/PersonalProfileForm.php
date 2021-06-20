<?php

namespace Drupal\contacts_jobs_candidates\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class PersonalProfileForm.
 *
 * Custom form for profile information step of candidate registration. Combines
 * fields from user entity, candidate profile and crm_indiv profile in to a
 * single form.
 *
 * @package Drupal\contacts_jobs_candidates\Form
 */
class PersonalProfileForm extends CandidateRegistrationBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_jobs_candidates.personal_profile';
  }

  /**
   * {@inheritdoc}
   */
  protected function getCardNames(): array {
    return ['personal_details', 'contact_details'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form = $this->buildPersonalDetailsCard($form, $form_state);
    $form = $this->buildContactDetailsCard($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('contacts_jobs_candidates.cv_resume', [
      'user' => $this->user->id(),
    ]);
  }

  /**
   * Build the Personal Details card of the Profile step.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form array with the personal details card added.
   */
  protected function buildPersonalDetailsCard(array $form, FormStateInterface $form_state) {
    $form['personal_details'] = [
      '#type' => 'fieldset',
      '#parents' => [],
      '#attributes' => [
        'class' => ['card', 'card-body'],
      ],
    ];

    $form['personal_details']['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Personal details'),
    ];

    $fields = [
      'crm_name' => [
        'namespace' => 'profile',
        'config' => [],
      ],
      'crm_address' => [
        'namespace' => 'profile',
        'config' => [],
      ],
      'crm_dob' => [
        'namespace' => 'profile',
        'config' => [],
      ],
      'nationality' => [
        'namespace' => 'candidateProfile',
        'config' => [],
      ],
      'right_to_work' => [
        'namespace' => 'candidateProfile',
        'config' => [],
      ],
      'able_to_drive' => [
        'namespace' => 'candidateProfile',
        'config' => [],
      ],
      'crm_photo' => [
        'namespace' => 'profile',
        'config' => [],
      ],
      'personal_statement' => [
        'namespace' => 'candidateProfile',
        'config' => [],
      ],
    ];

    $this->addFormWidgets($form['personal_details'], $form_state, $fields);

    return $form;
  }

  /**
   * Build the Contact Details card of the Profile step.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form array with the contact details card added.
   */
  protected function buildContactDetailsCard(array $form, FormStateInterface $form_state) {
    $form['contact_details'] = [
      '#type' => 'fieldset',
      '#parents' => [],
      '#attributes' => [
        'class' => ['card', 'card-body'],
      ],
    ];

    $form['contact_details']['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Contact details'),
    ];

    $fields = [
      'phone_number' => [
        'namespace' => 'candidateProfile',
        'config' => [],
      ],
    ];

    $this->addFormWidgets($form['contact_details'], $form_state, $fields);

    return $form;
  }

}
