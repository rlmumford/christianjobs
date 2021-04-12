<?php

namespace Drupal\contacts_jobs_dashboard\Form;

use Drupal\contacts\Form\AddOrgForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding a recruiter organisation.
 *
 * @package Drupal\contacts_jobs\Form
 */
class NewRecruiterOrgForm extends AddOrgForm implements TrustedCallbackInterface {

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->elementInfoManager = $container->get('plugin.manager.element_info');
    $form->moduleHandler = $container->get('module_handler');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['wrapForm'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_job_dashboard_add_recruiter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#pre_render'][] = [static::class, 'wrapForm'];

    $form = parent::buildForm($form, $form_state);

    // Remove mail from parent implementation.
    unset($form['mail']);

    // Remove the wrapper around the headquarters.
    unset($form['headquarters']['widget']['#theme']);
    unset($form['headquarters']['widget'][0]['#type']);
    $form['headquarters']['widget'][0]['inline_entity_form']['#process'] = $this->elementInfoManager->getInfoProperty('inline_entity_form', '#process', []);
    // phpcs:ignore Drupal.Arrays.Array.LongLineDeclaration
    $form['headquarters']['widget'][0]['inline_entity_form']['#process'][] = [static::class, 'processHeadquarters'];

    // Add a form wrapper that will hide the entire form unless the new org
    // selection is made.
    $form['_wrapper'] = [
      '#type' => 'container',
      '#attached' => [
        'library' => ['contacts_jobs_dashboard/recruiter_registration'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="existing_org"]' => ['value' => ['regexp' => '.* \(new\)"?$']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Pre render callback to move elements into the form wrapper so states apply.
   *
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The wrapped form array.
   */
  public static function wrapForm(array $form): array {
    foreach (Element::children($form) as $child) {
      if ($child === '_wrapper') {
        continue;
      }
      $form['_wrapper'][$child] = $form[$child];
      $form['_wrapper']['#sorted'] = FALSE;
      unset($form[$child]);
    }
    return $form;
  }

  /**
   * Process callback to remove markup around headquarters address.
   *
   * @param array $element
   *   The element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified headquarters IEF.
   */
  public static function processHeadquarters(array $element, FormStateInterface $form_state): array {
    unset($element['address']['widget']['#theme']);
    unset($element['address']['widget'][0]['#type']);
    $element['address']['widget'][0]['address']['#default_value']['country_code'] = 'GB';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getUserFields(array $field_definitions): array {
    $fields = parent::getUserFields($field_definitions);
    //$fields['terms_and_conditions_org'] = ['weight' => 99];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function getProfileFields(array $field_definitions): array {
    //$field_definitions['fmcg_currency_code']->setRequired(TRUE);
    return parent::getProfileFields($field_definitions) + [
      'crm_phone' => [],
      //'fmcg_currency_code' => ['type' => 'options_select'],
      'headquarters' => ['type' => 'inline_entity_form_simple'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->user->get('group')->entity;

    /** @var \Drupal\user\UserInterface $user */
    // Ensure we're definitely working with a user instance.
    $user = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser()->id());

    // Save the group and add the current user as a group admin.
    $group->save();
    $group->addMember($user, [
      'group_roles' => [
        'contacts_org-admin',
        'contacts_org-recruiter',
      ],
    ]);

    // Show a message and redirect to the recruiter's dashboard.
    $this->messenger()
      ->addMessage($this->t('Your organisation has been added. You can update your details at any time.'));

    if ($this->moduleHandler->moduleExists('contacts_jobs_subscriptions')) {
      $form_state->setRedirect(
        'contacts_jobs_subscriptions.manage',
        ['user' => $this->user->id()]
      );
    }
    else {
      $form_state->setRedirect(
        'contacts_user_dashboard.summary',
        ['user' => $this->user->id()]
      );
    }

  }

}
