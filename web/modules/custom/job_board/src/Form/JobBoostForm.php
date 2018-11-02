<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 01/11/2018
 * Time: 18:54
 */

namespace Drupal\job_board\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\job_role\Entity\JobRoleInterface;

class JobBoostForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'job_boost_form';
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
  public function buildForm(array $form, FormStateInterface $form_state, JobRoleInterface $job_role = NULL) {
    $form_state->set('job', $job_role);

    if (!$job_role->boost_start_date->isEmpty()) {
      $start_date = $job_role->boost_start_date->date->format('Y-m-d');
      $end_date = $job_role->boost_end_date->date->format('Y-m-d');
      $current_date = (new DrupalDateTime())->format('Y-m-d');

      if ($start_date <= $current_date && $current_date <= $end_date) {
        \Drupal::messenger()->addWarning(new TranslatableMarkup('@job is already boosted.', ['@job' => $job_role->label()]));
      }
    }

    $form['boost_start'] = [
      '#title' => new TranslatableMarkup('Start Date'),
      '#description' => new TranslatableMarkup('When the boost should start.'),
      '#type' => 'datetime',
      '#default_value' => $job_role->boost_start_date->value,
      '#date_increment' => 1,
      '#date_time_element' => 'none',
      '#date_timezone' => DateTimeItemInterface::STORAGE_TIMEZONE,
    ];

    $form['boost_days'] = [
      '#title' => new TranslatableMarkup('Boost Duration'),
      '#type' => 'select',
      '#default_value' => 3,
      '#options' => [1,2,3,4,5,6,7],
      '#field_suffix' => new TranslatableMarkup('days'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => new TranslatableMarkup('Confirm Boost'),
      ],
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
    $job = $form_state->get('job');
    $job->boost_start_date->value = $form_state->getValue('boost_start')->format('Y-m-d');

    $duration = 'P'.$form_state->getValue('boost_days', 1).'D';
    /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
    $start_date = clone $job->boost_start_date->date;
    $start_date->add(new \DateInterval($duration));
    $job->boost_end_date->value = $start_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);

    $job->save();

    $this->messenger()->addStatus(new TranslatableMarkup(
      'Boosted @job for @days days, starting @date',
      [
        '@job' => $job->label(),
        '@days' => $form_state->getValue('boost_days', 1),
        '@date' => $job->boost_start_date->date->format('d/m/Y'),
      ]
    ));

    \Drupal\Core\Cache\Cache::invalidateTags(['boosted_jobs']);
    $form_state->setRedirect('entity.job_role.canonical', ['job_role' => $job->id()]);
  }
}
