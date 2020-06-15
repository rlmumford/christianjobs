<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\organization\Entity\Organization;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class RecruiterMemberFormBase extends FormBase {

  /**
   * @var \Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem
   */
  protected $item;

  /**
   * @var bool
   */
  protected $allowCreate = FALSE;

  /**
   * @var []|string
   */
  protected $permittedStatus = [];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, UserInterface $user = NULL) {
    $this->item = $user->organization->getOrganizationItem($organization, $this->allowCreate);

    if (!$this->item) {
      throw new NotFoundHttpException();
    }

    if (!empty($this->permittedStatus)) {
      if (is_array($this->permittedStatus) && !in_array($this->item->status, $this->permittedStatus)) {
        throw new NotFoundHttpException();
      }
      else if (is_string($this->permittedStatus) && ($this->permittedStatus != $this->item->status)) {
        throw new NotFoundHttpException();
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Confirm'),
        '#submit' => [
          '::submitForm',
        ]
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('job_board.recruiter.team', ['organization' => $this->item->target_id]);
  }
}
