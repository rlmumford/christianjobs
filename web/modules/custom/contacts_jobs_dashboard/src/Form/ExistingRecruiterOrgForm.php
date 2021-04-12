<?php

namespace Drupal\contacts_jobs_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contacts_communication\BuildAndSendCommunicationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for selecting an existing organisation.
 *
 * @package Drupal\contacts_jobs_dashboard\Form
 */
class ExistingRecruiterOrgForm extends FormBase {

  use BuildAndSendCommunicationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $self = parent::create($container);
    $self->entityTypeManager = $container->get('entity_type.manager');
    return $self;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recruiter_org_existing_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['existing_org'] = [
      '#title' => $this->t('Search for your organisation'),
      '#description' => $this->t("Enter the name of your organisation and select it from the list. If you can't find it, select %cant_find.", [
        '%cant_find' => $this->t("+ Create"),
      ]),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_handler' => 'search_api:organisation',
      '#required' => TRUE,
      '#selection_settings' => [
        'index' => 'contacts_index',
        'conditions' => [
          ['roles', 'crm_org'],
        ],
        'create_option' => TRUE,
      ],
      '#attached' => [
        'library' => ['contacts_jobs_dashboard/recruiter_registration'],
      ],
      '#states' => [
        'invisible' => [
          ':input[name="existing_org"]' => ['value' => ['regexp' => '.* \(new\)"?$']],
        ],
      ],
    ];

    if (isset($form_state->getUserInput()['crm_org_name'][0]['value'])) {
      $form['existing_org']['#value'] = '"' . $form_state->getUserInput()['crm_org_name'][0]['value'] . ' (new)"';
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Request access to selected organisation'),
      '#submit' => ['::submitForm'],
      '#attached' => [
        'library' => ['contacts_jobs_dashboard/recruiter_registration'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="existing_org"]' => ['value' => ['regexp' => '.* \([0-9]+\)"?$']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $org_id = $form_state->getValue('existing_org');
    $org = $user_storage->load($org_id);

    /** @var \Drupal\user\UserInterface $user */
    // Ensure we're definitely working with a user instance.
    $user = $user_storage
      ->load($this->currentUser()->id());

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $org->get('group')->entity;
    if ($group->isNew()) {
      // Group must be saved before trying to add a member.
      $group->save();
    }
    // Calling addMember saves internally.
    // Adding without any group roles as that will be handled by approval step.
    $group->addMember($user);

    foreach ($group->getMembers(['contacts_org-admin']) as $notify_member) {
      $this->buildAndSendCommunication([
        'recipient' => $notify_member->getUser(),
        'requester' => $user,
        'organisation' => $notify_member->getGroup()->contacts_org->entity,
      ], 'config:join_organisation_request_email');
    }

    $this->messenger()->addMessage($this->t('Thank you for selecting your organisation.'));

    $form_state->setRedirect('contacts_user_dashboard.summary', [
      'user' => $this->currentUser()->id(),
    ]);
  }

}
