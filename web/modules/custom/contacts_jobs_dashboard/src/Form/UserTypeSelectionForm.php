<?php

namespace Drupal\contacts_jobs_dashboard\Form;

use Drupal\contacts_jobs_dashboard\UserHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides the user type selection post registration.
 */
class UserTypeSelectionForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user helper service.
   *
   * @var \Drupal\contacts_jobs_dashboard\UserHelper
   */
  protected $userHelper;

  /**
   * Construct the form object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\contacts_jobs_dashboard\UserHelper $user_helper
   *   The user helper service.
   */
  protected function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, UserHelper $user_helper) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->userHelper = $user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return (new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('contacts_jobs_dashboard.user_helper'),
    ))
      ->setStringTranslation($container->get('string_translation'))
      ->setRequestStack($container->get('request_stack'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_jobs_dashboard_user_type_selection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If we already have any roles other than crm_indiv, redirect onto the
    // destination if set, otherwise the user dashboard.
    if (array_diff($this->currentUser->getRoles(TRUE), ['crm_indiv'])) {
      $destination = $this->requestStack
        ->getCurrentRequest()
        ->query
        ->get('destination');
      if ($destination) {
        $destination = Url::fromUserInput($destination);
      }
      else {
        $destination = Url::fromRoute('user.page');
      }

      $destination->setAbsolute(TRUE);
      return new RedirectResponse($destination->toString());
    }

    $form['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('What type of account would you like?'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['recruiter'] = [
      '#type' => 'submit',
      '#value' => $this->t('Recruiter'),
      '#user_role' => 'recruiter',
    ];
    $form['actions']['candidate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Candidate'),
      '#user_role' => 'candidate',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Double check the user is still awaiting a role to prevent accidentally
    // applying both.
    if (array_diff($this->currentUser->getRoles(TRUE), ['crm_indiv'])) {
      $form_state->setError($form, $this->t('It looks like you have already selected your role.'));
    }

    // Ensure the submit button has a valid role.
    $role = $form_state->getTriggeringElement()['#user_role'] ?? NULL;
    if (!in_array($role, ['recruiter', 'candidate'], TRUE)) {
      $form_state->setError($form, $this->t('Sorry, your selection is invalid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $role = $form_state->getTriggeringElement()['#user_role'];
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());
    $user->addRole($role);
    $user->save();

    // Redirect based on the role.
    $destination = $this->userHelper->getRegistrationDestination($user) ??
      Url::fromRoute('user.page');
    $form_state->setRedirectUrl($destination);
  }

}
