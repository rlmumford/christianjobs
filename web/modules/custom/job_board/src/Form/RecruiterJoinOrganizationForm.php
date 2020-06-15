<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\organization\Entity\Organization;
use Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RecruiterJoinOrganizationForm extends FormBase {

  /**
   * @var \Drupal\organization\Entity\Organization
   */
  protected $organization;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recruiter_join_organization_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Organization $organization = NULL, UserInterface $user = NULL) {
    $this->organization = $organization;
    $this->user = $user ?: $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    /** @var OrganizationMetadataReferenceItem $item */
    if ($item = $this->user->organization->getOrganizationItem($organization, FALSE)) {
      if ($item->status === OrganizationMetadataReferenceItem::STATUS_REQUESTED) {
        $message = new TranslatableMarkup('You already have an oustanding request to join @organization.', ['@organization' => $this->organization->label()]);
      }
      else if ($item->status === OrganizationMetadataReferenceItem::STATUS_ACTIVE) {
        $message = new TranslatableMarkup('You are already part of @organization.', ['@organization' => $this->organization->label()]);
      }
      else if ($item->status === OrganizationMetadataReferenceItem::STATUS_BLOCKED) {
        $message = new TranslatableMarkup('It is not possible to join @organization', ['@organization' => $this->organization->label()]);
      }
      else if ($item->status === OrganizationMetadataReferenceItem::STATUS_INVITED) {
        $item->status = OrganizationMetadataReferenceItem::STATUS_ACTIVE;
        $this->user->save();

        $this->messenger()->addStatus(new TranslatableMarkup('You are now a member of @organization.', ['@organization' => $this->organization->label()]));

        return new RedirectResponse(Url::fromRoute('view.job_board__recruiter_jobs.page', ['organization' => $this->organization->id()])->toString());
      }

      $form['message'] = [
        '#markup' => $message,
      ];

      return $form;
    }

    $form['message'] = [
      '#markup' => new TranslatableMarkup(
        'Are you sure you want to join @organization? This will send a request to the organization administrators. You may wish to contact them separately.',
        [
          '@organization' => $organization->label(),
        ]
      ),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Request to Join'),
        '#submit' => [
          '::submitForm',
        ]
      ],
    ];

    return $form;
  }

  /**
   * [@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->user->organization[] = [
      'target_id' => $this->organization->id(),
      'status' => OrganizationMetadataReferenceItem::STATUS_REQUESTED,
      'role' => OrganizationMetadataReferenceItem::ROLE_MEMBER,
    ];
    $this->user->save();

    $this->messenger()->addStatus(
      new TranslatableMarkup('Your request to join this organization has been received.')
    );
  }
}
