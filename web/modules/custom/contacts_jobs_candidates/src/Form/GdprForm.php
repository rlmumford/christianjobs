<?php

namespace Drupal\contacts_jobs_candidates\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

/**
 * Class GdprForm.
 *
 * Custom form for GDPR step of candidate registration.
 *
 * @package Drupal\contacts_jobs_candidates\Form
 */
class GdprForm extends CandidateRegistrationBaseForm implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderGroups'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_jobs_candidates.gdpr';
  }

  /**
   * {@inheritdoc}
   */
  protected function getCardNames(): array {
    return ['gdpr'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form = $this->buildGdprCard($form, $form_state);

    if (!$this->requestStack->getCurrentRequest()->query->has('destination')) {
      $form['actions']['submit']['#value'] = $this->t('Finish');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('contacts_user_dashboard.summary', [
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
    return Url::fromRoute('contacts_jobs_candidates.job_target', [
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

    $form_state->setRedirect('contacts_jobs_candidates.job_target', [
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
  protected function buildGdprCard(array $form, FormStateInterface $form_state) {
    $form['gdpr'] = [
      '#type' => 'fieldset',
      '#parents' => [],
      '#attributes' => [
        'class' => ['card', 'card-body'],
      ],
      '#pre_render' => [[$this, 'preRenderGroups']],
    ];

    $form['gdpr']['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Contact Consent'),
    ];

    $fields = [
      'consent_comms' => [
        'namespace' => 'candidateProfile',
        'config' => [],
      ],
    ];
    $this->addFormWidgets($form['gdpr'], $form_state, $fields);

    // Add the visual grouping.
    $form['gdpr']['email'] = [
      '#type' => 'fieldset',
      '#parents' => ['gdpr'],
      '#title' => $this->t('Email'),
      '#weight' => 5,
      '#group_fields' => [
        'comms_consent_email_any',
        'comms_consent_email_targetted',
      ],
    ];

    $form['gdpr']['phone'] = [
      '#type' => 'fieldset',
      '#parents' => ['gdpr'],
      '#title' => $this->t('Phone'),
      '#weight' => 10,
      '#group_fields' => [
        'comms_consent_phone_any',
        'comms_consent_phone_targetted',
      ],
    ];

    $form['gdpr']['text'] = [
      '#type' => 'fieldset',
      '#parents' => ['gdpr'],
      '#title' => $this->t('Text'),
      '#weight' => 15,
      '#group_fields' => [
        'comms_consent_text_any',
        'comms_consent_text_targetted',
      ],
    ];

    $fields = [
      'comms_consent_email_any' => [
        'namespace' => 'candidateProfile',
        'config' => [],
        'label' => $this->t('Any opportunity'),
      ],
      'comms_consent_email_targetted' => [
        'namespace' => 'candidateProfile',
        'config' => [],
        'label' => $this->t('Opportunities that match my targets'),
      ],
      'comms_consent_phone_any' => [
        'namespace' => 'candidateProfile',
        'config' => [],
        'label' => $this->t('Any opportunity'),
      ],
      'comms_consent_phone_targetted' => [
        'namespace' => 'candidateProfile',
        'config' => [],
        'label' => $this->t('Opportunities that match my targets'),
      ],
      'comms_consent_text_any' => [
        'namespace' => 'candidateProfile',
        'config' => [],
        'label' => $this->t('Any opportunity'),
      ],
      'comms_consent_text_targetted' => [
        'namespace' => 'candidateProfile',
        'config' => [],
        'label' => $this->t('Opportunities that match my targets'),
      ],
    ];
    $this->addFormWidgets($form['gdpr'], $form_state, $fields);
    foreach ($fields as $field_name => $options) {
      $form['gdpr'][$field_name]['widget'][0]['agreed']['#title'] = $options['label'];
      $form['gdpr'][$field_name]['widget'][0]['agreed']['#title'] = $options['label'];
      unset($form['gdpr'][$field_name]['widget'][0]['agreed']['#description']);
      unset($form['gdpr'][$field_name]['widget'][0]['agreed']['#attached']);
    }

    return $form;
  }

  /**
   * Pre render callback to group consent fields.
   *
   * @param array $element
   *   The form element.
   *
   * @return array
   *   The adjusted form element.
   */
  public function preRenderGroups(array $element): array {
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]['#group_fields'])) {
        foreach ($element[$key]['#group_fields'] as $child_key) {
          if (isset($element[$child_key])) {
            $element[$key][$child_key] = $element[$child_key];
            unset($element[$child_key]);
          }
        }
      }
    }
    return $element;
  }

}
