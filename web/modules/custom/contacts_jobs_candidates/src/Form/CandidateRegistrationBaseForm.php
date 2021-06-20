<?php

namespace Drupal\contacts_jobs_candidates\Form;

use Drupal\contacts\Form\AddContactBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CandidateRegistrationBaseForm.
 *
 * Base form for candidate registration step forms.
 *
 * @package Drupal\fmcg_candidate\Form
 */
abstract class CandidateRegistrationBaseForm extends AddContactBase {
  /**
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Candidate profile for this user.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $candidateProfile;

  /**
   * An array of fields on attached entities.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fields;

  /**
   * Defines the cards that exist on this form.
   *
   * @return array
   *   The cards for the form.
   */
  abstract protected function getCardNames(): array;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $self = parent::create($container);

    $self->currentRoute = $container->get('current_route_match');
    $self->formBuilder = $container->get('entity.form_builder');
    $self->profileStorage = $container->get('entity_type.manager')->getStorage('profile');
    $self->requestStack = $container->get('request_stack');

    $self->user = $self->currentRoute->getParameter('user');

    return $self;
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state): void {
    // Do not call parent::init() as we do not want to replace $this->user.
    $form_state->set('entity_form_initialized', TRUE);

    $this->profile = $this->profileStorage->loadByUser($this->user, 'crm_indiv') ?? $this->profileStorage->create([
      'type' => 'crm_indiv',
      'status' => TRUE,
      'is_default' => TRUE,
    ]);

    $this->candidateProfile = $this->profileStorage->loadByUser($this->user, 'candidate') ?? $this->profileStorage->create([
      'type' => 'candidate',
      'status' => TRUE,
      'is_default' => TRUE,
    ]);

    $this->fields = $this->entityFieldManager->getFieldDefinitions('profile', $this->profile->bundle());
    $this->fields += $this->entityFieldManager->getFieldDefinitions('profile', $this->candidateProfile->bundle());
    $this->fields += $this->entityFieldManager->getFieldDefinitions('user', 'user');

  }

  /**
   * Copy form values onto our entities.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The built entities.
   */
  protected function buildEntities(array $form, FormStateInterface $form_state): array {
    foreach (['user', 'profile', 'candidateProfile'] as $namespace) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $candidate_profile */
      $entity = clone $this->{$namespace};
      $entity->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));

      $fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

      foreach ($this->getCardNames() as $card_name) {
        foreach (Element::children($form[$card_name]) as $field_name) {
          if ($namespace !== ($form[$card_name][$field_name]['#entity_namespace'] ?? NULL)) {
            continue;
          }

          $widget = $this->getWidget($fields, $field_name, $form[$card_name][$field_name]['#widget_configuration']);
          $widget->extractFormValues($entity->get($field_name), $form, $form_state);
        }
      }

      $entities[$namespace] = $entity;
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    unset($form['mail']);

    $current_request = $this->requestStack->getCurrentRequest();
    $has_destination = $current_request->query->has('destination');
    $form['actions']['submit']['#value'] = $has_destination ?
      $this->t('Save') :
      $this->t('Continue');

    if ($has_destination || method_exists($this, 'goBackUrl')) {
      if ($has_destination) {
        $url = Url::fromUserInput($current_request->query->get('destination'));
      }
      else {
        $url = $this->goBackUrl();
      }
      $form['actions']['back'] = [
        '#type' => 'link',
        '#title' => $this->t('Go back'),
        '#url' => $url,
        '#attributes' => ['class' => ['btn', 'btn-link']],
      ];
    }
    elseif (method_exists($this, 'goBackSubmit')) {
      $form['actions']['back'] = [
        '#type' => 'submit',
        // Do not validate the form when going back.
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['btn', 'btn-link']],
        '#value' => $this->t('Go back'),
        '#submit' => [[$this, 'goBackSubmit']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->candidateProfile->setOwner($this->user);
    $this->candidateProfile->save();
  }

  /**
   * Access callback for Candidate Registration forms.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $user = $route_match->getParameter('user');

    if (!$user instanceof UserInterface) {
      return AccessResult::forbidden('No matching user');
    }

    if ($user->id() == $account->id()) {
      return AccessResult::allowedIfHasPermission($account, 'update own crm_indiv profile');
    }

    return AccessResult::forbidden('Not the current user');
  }

  /**
   * Add form widgets to the form for a selection of fields.
   *
   * @param array $form
   *   The form array to add the fields to. May be a subset of the whole form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form to add the widgets to.
   * @param array $fields
   *   An array of the fields with the keys:
   *   - entity: The entity that the field exists on.
   *   - configuration: An array of configuration for the field widget.
   */
  protected function addFormWidgets(array &$form, FormStateInterface $form_state, array $fields) {
    assert(isset($form['#parents']), 'Form must have the #parents key set.');

    $weight = 0;
    foreach ($fields as $field_name => $field_config) {
      $entity = $this->{$field_config['namespace']};
      $form[$field_name] = $this->getWidgetForm($entity, $this->fields, $field_name, $form, $form_state, $field_config['config']);
      $form[$field_name]['#entity_namespace'] = $field_config['namespace'];
      $form[$field_name]['#weight'] = $weight++;
    }
  }

  /**
   * {@inheritdoc}
   *
   * BuildForm is set up to mix fields from different profiles so do nothing
   * with profile fields in the parent.
   */
  protected function getProfileFields(array $field_definitions): array {
    return [];
  }

}
