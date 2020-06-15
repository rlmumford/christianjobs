<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem;

class RecruiterRegisterOrganizationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'job_board/organization-duplicates';

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
   *
   * @return int|void
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    // Set the organization on the current user.
    $user = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser()->id());
    $user->organization[] = [
      'target_id' => $this->entity->id(),
      'status' => OrganizationMetadataReferenceItem::STATUS_ACTIVE,
      'role' => OrganizationMetadataReferenceItem::ROLE_OWNER,
    ];
    $user->save();

    return $return;
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
      '#items' => [],
    ];

    foreach ($this->entityTypeManager->getStorage('organization')->loadMultiple($ids) as $organization) {
      $content['#items'][] = new TranslatableMarkup('@organization @button', [
        '@organization' => $organization->label()." (".$organization->headquarters->entity->address->country_code.")",
        '@button' => Link::createFromRoute(
          new TranslatableMarkup('Request to Join'),
          'job_board.recruiter.register.join_organization',
          ['organization' => $organization->id()]
        )->toString(),
      ]);
    }

    $response->addCommand(new OpenModalDialogCommand(
      'Other Organizations',
      $content,
      ['width' => '700']
    ));

    return $response;
  }

}
