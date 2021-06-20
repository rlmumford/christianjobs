<?php

namespace Drupal\contacts_jobs_candidates\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class JobTargetStepForm.
 *
 * Custom form for Job Target step of candidate registration.
 *
 * @package Drupal\contacts_jobs_candidates\Form
 */
class JobTargetStepForm extends CandidateRegistrationBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_jobs_candidates.job_target';
  }

  /**
   * {@inheritdoc}
   */
  protected function getCardNames(): array {
    return ['job_target'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form = $this->buildJobTargetsCard($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('contacts_jobs_candidates.gdpr', [
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
    return Url::fromRoute('contacts_jobs_candidates.cv_resume', [
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

    $form_state->setRedirect('contacts_jobs_candidates.cv_resume', [
      'user' => $this->user->id(),
    ]);
  }

  /**
   * Build the Job target criteria card of the Profile step.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form array with the job targets card added.
   */
  protected function buildJobTargetsCard(array $form, FormStateInterface $form_state) {
    $form['job_target'] = [
      '#type' => 'fieldset',
      '#parents' => [],
      '#attributes' => [
        'class' => ['card', 'card-body'],
      ],
    ];

    $fields = [
      'job_targets' => [
        'namespace' => 'candidateProfile',
        'config' => ['type' => 'inline_entity_form_complex'],
      ],
    ];

    $this->addFormWidgets($form['job_target'], $form_state, $fields);

    return $form;
  }

}
