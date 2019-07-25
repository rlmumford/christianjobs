<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class JobForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['label']['#weight'] = -10;
    $form['industry']['#weight'] = -7;
    $form['keywords']['#weight'] = -6;

    $form['description_summary']['widget'][0]['#maxlength'] = 350;
    $form['description_summary']['widget'][0]['#placeholder'] = t('Enter a short summary of your opportunity.');
    $form['description_summary']['widget'][0]['#rows'] = 4;
    $form['description_summary']['widget'][0]['#format'] = 'restricted_html';

    $form['location']['widget'][0]['type'] = [
      '#type' => 'select',
      '#weight' => -1,
      '#title' => new TranslatableMarkup('Type'),
      '#options' => [
        'home' => new TranslatableMarkup('Home based'),
        'remote' => new TranslatableMarkup('Remote working'),
        'office' => new TranslatableMarkup('Office based'),
        'location' => new TranslatableMarkup('On location'),
      ],
      '#default_value' => $this->entity->location_type->value,
    ];

    $form['salary']['widget'][0]['compensation'] = [
      '#type' => 'select',
      '#weight' => -1,
      '#title' => new TranslatableMarkup('Type'),
      '#options' => [
        'volunteer' => t('Volunteer'),
        'apprentice' => t('Apprentice'),
        'pro_rate' => t('Pro-Rata'),
        'salaried' => t('Salaried'),
        'self_funded' => t('Self-Funded'),
      ],
      '#default_value' => $this->entity->compensation->value,
    ];
    $form['compensation']['#access'] = FALSE;

    // Move the contact_ fields into their own section.
    $form['contact_details'] = [
      '#weight' => 48,
      '#type' => 'container',
      '#attributes' => [
        'class' => ['divider-top'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Contact Details'),
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => [
          'class' => ['section-summary'],
        ],
        '#value' => $this->t('Please provide any contact information an applicant may need to apply for the job.'),
      ],
    ];
    foreach (['contact_address', 'contact_email', 'contact_phone'] as $contact_field) {
      $form['contact_details'][$contact_field] = $form[$contact_field];
      unset($form[$contact_field]);
    }
    $form['contact_details']['contact_phone']['#weight'] = 100;

    // Make the salary widget more useable.
    $form['salary']['widget']['0']['#element_validate'] = [static::class.'::salaryWidgetValidate'];
    $form['salary']['widget']['0']['from']['#error_no_message'] = TRUE;
    $form['salary']['widget']['0']['to']['#error_no_message'] = TRUE;
    $form['salary']['widget']['0']['#type'] = 'details';
    $form['salary']['widget']['0']['#open'] = TRUE;

    // Don't use chrome validation.
    $form['#attributes']['novalidate'] = 'novalidate';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if ($location_type = $form_state->getValue(['location', '0', 'type'])) {
      $this->entity->location_type = $location_type;
    }
    if ($salary_type = $form_state->getValue(['salary', '0', 'compensation'])) {
      $this->entity->compensation = $salary_type;
    }

    $form_state->setRedirect(
      'entity.job_role.canonical',
      ['job_role' => $this->getEntity()->id()]
    );
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['#attributes']['class'][] = 'divider-top';
    return $actions;
  }

  /**
   * Validate that both part of the range are filled if any are.
   */
  public static function salaryWidgetValidate($element, FormStateInterface $form_state, $complete_form) {
    $values = $form_state->getValue($element['#parents']);
    if ((!empty($values['from']) && empty($values['to'])) || (!empty($values['to']) && empty($values['from']) && $values['from'] !== '0')) {
      $form_state->setError($element, 'Please provide both parts of the salary range.');
    }
    if (!empty($values['from']) && !empty($values['to']) && ($values['from'] > $values['to'])) {
      $form_state->setError($element, 'Please ensure that the salary from value is less than the salary to value.');
    }
  }

}
