<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\organization\Entity\Organization;
use Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem;
use Drupal\user\UserInterface;

class RecruiterMemberChangeRoleForm extends RecruiterMemberFormBase {

  /**
   * {@inheritdoc}
   *
   * This form only applies to member requests.
   */
  protected $permittedStatus = [
    OrganizationMetadataReferenceItem::STATUS_ACTIVE
  ];

  /**
   * [@inheritdoc}
   */
  public function getFormId() {
    return 'recruiter_member_change_role_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, UserInterface $user = NULL) {
    $form = parent::buildForm($form, $form_state, $organization, $user);

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#options' => [
        OrganizationMetadataReferenceItem::ROLE_MEMBER => $this->t('Member'),
        OrganizationMetadataReferenceItem::ROLE_ADMIN => $this->t('Administrator'),
        OrganizationMetadataReferenceItem::ROLE_OBSERVER => $this->t('Observer'),
        OrganizationMetadataReferenceItem::ROLE_OWNER => $this->t('Owner'),
      ],
      '#default_value' => $this->item->role,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->item->role = $form_state->getValue('role');
    $this->item->getEntity()->save();
  }
}
