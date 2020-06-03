<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class RecruiterRegisterOrganizationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['find_dups'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Check for Duplicates'),
      '#submit' => [
        '::submitFormCheckDuplicates',
      ],
      '#limit_validation_errors' => [
        ['name'],
        ['website'],
        ['headquarters', 0, 'inline_entity_form', 'address', 0, 'address', 'country_code'],
      ],
      '#name' => 'find_dups',
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#ajax' => [
        'callback' => '::ajaxFormCheckDuplicates',
      ]
    ];

    $form['name']['widget'][0]['value']['#ajax'] = [
      'callback' => '::ajaxFormCheckDuplicates',
      'event' => 'change',
      'trigger_as' => ['name' => 'find_dups'],
    ];
    $form['website']['widget'][0]['uri']['#ajax'] = [
      'callback' => '::ajaxFormCheckDuplicates',
      'event' => 'change',
      'trigger_as' => ['name' => 'find_dups'],
    ];

    // @todo: Add listener to headquarters using ajax.

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitFormCheckDuplicates(array $form, FormStateInterface $form_state) {

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function ajaxFormCheckDuplicates(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $values = array_filter([
      'name' => $form_state->getValue(['name', 0, 'value']),
      'website.uri' => $form_state->getValue(['website', 0, 'uri']),
      'headquarters.entity.address.country_code' => $form_state->getValue(['headquarters', 0, 'inline_entity_form', 'address', 0, 'address', 'country_code']),
    ]);
    if (count($values) < 2) {
      return $response;
    }

    $query = $this->entityTypeManager->getStorage('organization')->getQuery();
    foreach ($values as $field => $value) {
      $query->condition($field, $value);
    }
    $ids = $query->execute();

    if (empty($ids)) {
      return $response;
    }

    $content = [
      '#theme' => 'item_list',
      '#items' => [
        'Test 1 <a class="button">Request to Join</a>',
        'Test 2 <a class="button">Request to Join</a>',
        'Test 3 <a class="button">Request to Join</a>',
      ],
    ];

    $response->addCommand(new OpenModalDialogCommand(
      'Other Organizations',
      $content,
      ['width' => '700']
    ));

    return $response;
  }

}
