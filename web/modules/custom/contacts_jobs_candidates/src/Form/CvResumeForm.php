<?php

namespace Drupal\contacts_jobs_candidates\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class CvResumeForm.
 *
 * Custom form for CV/Resume step of candidate registration.
 *
 * @package Drupal\contacts_jobs_candidates\Form
 */
class CvResumeForm extends CandidateRegistrationBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_jobs_candidates.cv_resume';
  }

  /**
   * {@inheritdoc}
   */
  protected function getCardNames(): array {
    return [
      'cv_upload',
      'professional_qualifications',
      'education_qualifications',
      'employment_history',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form = $this->buildCvUpload($form, $form_state);
    $form = $this->buildProfessionalQualificationsCard($form, $form_state);
    $form = $this->buildEducationQualificationsCard($form, $form_state);
    $form = $this->buildEmploymentHistoryCard($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('contacts_jobs_candidates.job_target', [
      'user' => $this->user->id(),
    ]);
  }

  /**
   * Get the url for the back route.
   *
   * @return \Drupal\Core\Url
   *   The url to go back to.
   */
  public function goBackUrl() {
    return Url::fromRoute('contacts_jobs_candidates.personal_profile', [
      'user' => $this->user->id(),
    ]);
  }

  /**
   * Submit handler for going back to previous step.
   *
   * @param array $form
   *   The form being submitted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state being submitted.
   */
  public function goBackSubmit(array &$form, FormStateInterface $form_state) {
    // Save any changes anyway.
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('contacts_jobs_candidates.personal_profile', [
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
  protected function buildCvUpload(array $form, FormStateInterface $form_state) {
    $fields = [
      'cv_resume' => [
        'namespace' => 'candidateProfile',
        'config' => [],
      ],
    ];
    $form['cv_upload'] = ['#parents' => []];
    $this->addFormWidgets($form['cv_upload'], $form_state, $fields);

    return $form;
  }

  /**
   * Build the Professional Qualifications card of the Profile step.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form array with the professional qualifications card added.
   */
  protected function buildProfessionalQualificationsCard(array $form, FormStateInterface $form_state) {
    $form['professional_qualifications'] = [
      '#type' => 'fieldset',
      '#parents' => [],
      '#attributes' => [
        'class' => ['card', 'card-body'],
      ],
    ];

    $fields = [
      'professional_qualifications' => [
        'namespace' => 'candidateProfile',
        'config' => ['type' => 'inline_entity_form_complex'],
      ],
    ];

    $this->addFormWidgets($form['professional_qualifications'], $form_state, $fields);
    $form['professional_qualifications']['professional_qualifications']['widget']['#type'] = 'fieldset';
    return $form;
  }

  /**
   * Build the Education Qualifications card of the Profile step.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form array with the education qualifications card added.
   */
  protected function buildEducationQualificationsCard(array $form, FormStateInterface $form_state) {
    $form['education_qualifications'] = [
      '#type' => 'fieldset',
      '#parents' => [],
      '#attributes' => [
        'class' => ['card', 'card-body'],
      ],
    ];

    $fields = [
      'education_qualifications' => [
        'namespace' => 'candidateProfile',
        'config' => ['type' => 'inline_entity_form_complex'],
      ],
    ];

    $this->addFormWidgets($form['education_qualifications'], $form_state, $fields);
    $form['education_qualifications']['education_qualifications']['widget']['#type'] = 'fieldset';
    return $form;
  }

  /**
   * Build the Employment History card of the Profile step.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form array with the employment history card added.
   */
  protected function buildEmploymentHistoryCard(array $form, FormStateInterface $form_state) {
    $form['employment_history'] = [
      '#type' => 'fieldset',
      '#parents' => [],
      '#attributes' => [
        'class' => ['card', 'card-body'],
      ],
    ];

    $fields = [
      'employment_history' => [
        'namespace' => 'candidateProfile',
        'config' => ['type' => 'inline_entity_form_complex'],
      ],
    ];

    $this->addFormWidgets($form['employment_history'], $form_state, $fields);
    $form['employment_history']['employment_history']['widget']['#type'] = 'fieldset';
    return $form;
  }

}
