<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobPostOrganizationForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'job_post_organization_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#title_display' => 'invisible',
      '#options' => [
        'select' => $this->t('Find Organization'),
        'create' => $this->t('Create New'),
      ],
      '#default_value' => 'select',
    ];

    $form['select'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="mode"]' => [
            'value' => 'select',
          ],
        ],
      ],
    ];
    $form['select']['organization'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Search'),
      '#target_type' => 'organization',
    ];

    $form['create'] = [
      '#parents' => ['create'],
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="mode"]' => [
            'value' => 'create',
          ]
        ]
      ]
    ];

    if (!($organization = $form_state->get('organization'))) {
      $organization = $this->entityTypeManager->getStorage('organization')->create([]);
      $form_state->set('organization', $organization);
    }
    /** @var \Drupal\organization\Entity\Organization $organization */

    if (!$this->getFormDisplay($form_state)) {
      $form_display = EntityFormDisplay::collectRenderDisplay($organization, 'employer_details');
      $this->setFormDisplay($form_display, $form_state);
    }

    $this->getFormDisplay($form_state)->buildForm($organization, $form['create'], $form_state);

    $form['actions'] = [
      '#type' => 'actions',
      'select' => [
        '#type' => 'submit',
        '#value' => $this->t('Request to Join'),
        '#limit_validation_errors' => [
          ['select'],
          ['actions', 'create'],
        ],
        '#submit' => [
          '::submitFormSelect',
        ],
        '#validate' => [
          '::validateFormSelect',
        ],
        '#states' => [
          'visible' => [
            ':input[name="mode"]' => [
              'value' => 'select',
            ],
          ],
        ],
      ],
      'create' => [
        '#type' => 'submit',
        '#value' => $this->t('Create Organization'),
        '#limit_validation_errors' => [
          ['create'],
          ['actions', 'create'],
        ],
        '#submit' => [
          '::submitFormCreate',
        ],
        '#validate' => [
          '::validateFormCreate',
        ],
        '#states' => [
          'visible' => [
            ':input[name="mode"]' => [
              'value' => 'create',
            ]
          ]
        ]
      ]
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

  public function validateFormCreate(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\organization\Entity\Organization $organization */
    $organization = clone $form_state->get('organization');
    $extracted = $this->getFormDisplay($form_state)->extractFormValues($organization, $form, $form_state);

    // Then extract the values of fields that are not rendered through widgets,
    // by simply copying from top-level form values. This leaves the fields
    // that are not being edited within this form untouched.
    foreach ($form_state->getValues() as $name => $values) {
      if ($organization->hasField($name) && !isset($extracted[$name])) {
        $organization->set($name, $values);
      }
    }

    // Mark the entity as requiring validation.
    $organization->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));

    $violations = $organization->validate();
    // Remove violations of inaccessible fields.
    $violations->filterByFieldAccess($this->currentUser());

    // In case a field-level submit button is clicked, for example the 'Add
    // another item' button for multi-value fields or the 'Upload' button for a
    // File or an Image field, make sure that we only keep violations for that
    // specific field.
    $edited_fields = [];
    if ($limit_validation_errors = $form_state->getLimitValidationErrors()) {
      foreach ($limit_validation_errors as $section) {
        $field_name = reset($section);
        if ($organization->hasField($field_name)) {
          $edited_fields[] = $field_name;
        }
      }
      $edited_fields = array_unique($edited_fields);
    }
    else {
      $edited_fields = array_keys($this->getFormDisplay($form_state)->getComponents());
    }

    // Remove violations for fields that are not edited.
    $violations->filterByFields(array_diff(array_keys($organization->getFieldDefinitions()), $edited_fields));

    // Flag entity level violations.
    foreach ($violations->getEntityViolations() as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state->setErrorByName(str_replace('.', '][', $violation->getPropertyPath()), $violation->getMessage());
    }
    // Let the form display flag violations of its fields.
    $this->getFormDisplay($form_state)->flagWidgetsErrorsFromViolations($violations, $form, $form_state);

    // The entity was validated.
    $organization->setValidationRequired(FALSE);
    $form_state->setTemporaryValue('entity_validated', TRUE);

    return $organization;
  }

  public function validateFormSelect(array $form, FormStateInterface $form_state) {

  }

  public function submitFormCreate(array $form, FormStateInterface $form_state) {

  }

  public function submitFormSelect(array $form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(FormStateInterface $form_state) {
    return $form_state->get('form_display');
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, FormStateInterface $form_state) {
    $form_state->set('form_display', $form_display);
    return $this;
  }
}
