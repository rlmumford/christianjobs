<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class JobForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attributes']['class'][] = 'card';
    $form['#attributes']['class'][] = 'card-main';

    $form['salary']['widget']['0']['#element_validate'] = [JobPostForm::class.'::salaryWidgetValidate'];
    $form['salary']['widget']['0']['from']['#error_no_message'] = TRUE;
    $form['salary']['widget']['0']['to']['#error_no_message'] = TRUE;
    $form['salary']['widget']['0']['#type'] = 'details';
    $form['salary']['widget']['0']['#open'] = 'true';

    // Don't use chrome validation.
    $form['#attributes']['novalidate'] = 'novalidate';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect(
      'entity.job_role.canonical',
      ['job_role' => $this->getEntity()->id()]
    );
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|void
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['#attributes']['class'][] = 'card-item';
    $actions['#attributes']['class'][] = 'card-actions';
    $actions['#attributes']['class'][] = 'divider-top';
    return $actions;
  }

}
