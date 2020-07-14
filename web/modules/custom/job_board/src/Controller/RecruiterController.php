<?php

namespace Drupal\job_board\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\organization\Entity\Organization;
use Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecruiterController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_user')
    );
  }

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFormBuilderInterface $entity_form_builder,
    AccountInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
  }

  /**
   * Get the main page at /recruiter/
   */
  public function mainPage(AccountInterface $account) {
    $account = $account ?: $this->currentUser();

    if (!$account->isAuthenticated()) {
      return RedirectResponse::create(
        Url::fromRoute('job_board.recruiter.login')->toString()
      );
    }
    else {
      $user = $this->entityTypeManager->getStorage('user')->load(
        $account->id()
      );

      if ($user->organization->isEmpty() || $user->organization->status !== OrganizationMetadataReferenceItem::STATUS_ACTIVE) {
        throw new NotFoundHttpException();
      }

      return RedirectResponse::create(
        Url::fromRoute('job_board.employer', [
          'organization' => $user->organization->target_id,
        ])->toString()
      );
    }
  }

  public function mainPageAccess(AccountInterface $account) {
    $account = $account ?: $this->currentUser();
    $user = $this->entityTypeManager->getStorage('user')->load(
      $account->id()
    );

    $result = AccessResult::allowedIf(!$account->isAuthenticated())->addCacheableDependency($account);
    $result = $result->orIf(
      AccessResult::allowedIf(
        $user &&
        !$user->organization->isEmpty() &&
        $user->organization->status === OrganizationMetadataReferenceItem::STATUS_ACTIVE
      )
      ->addCacheableDependency($user)
    );

    return $result;
  }

  /**
   * Page to add a job
   *
   * @param \Drupal\organization\Entity\Organization $organization
   * @param \Drupal\user\UserInterface|NULL $owner
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addJob(Organization $organization, UserInterface $owner = NULL) {
    $owner = $owner ?: $this->currentUser();

    $job_storage = $this->entityTypeManager()->getStorage('job_role');
    $job = $job_storage->create([
      'owner' => $owner->id(),
      'organization' => $organization,
    ]);

    return $this->entityFormBuilder()->getForm($job, 'post');
  }

  /**
   * Access callback to add a job.
   *
   * @param \Drupal\organization\Entity\Organization $organization
   */
  public function addJobAccess(Organization $organization, AccountInterface $owner = NULL) {
    $manage_jobs_access = $organization->access("manage_job_roles", $owner, TRUE);

    return $manage_jobs_access;
    // @todo: Implement subscription check for access.
    $available_credit = $this->entityTypeManager()
      ->getStorage('job_board_job_credit')
      ->getQuery()
      ->condition('organization', $organization->id())
      ->condition('status', 'available')
      ->count()
      ->execute();

    return $manage_jobs_access->andIf(AccessResult::allowedIf($available_credit));
  }

  /**
   * Organization team page.
   *
   * @param \Drupal\organization\Entity\Organization $organization
   *
   * @return array
   */
  public function teamPage(Organization $organization) {
    $user_storage = $this->entityTypeManager()->getStorage('user');

    $ids = $user_storage->getQuery()
      ->condition('organization.target_id', $organization->id())
      ->execute();

    /** @var \Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem[][] $memberships */
    $memberships = [
      OrganizationMetadataReferenceItem::STATUS_ACTIVE => [],
    ];
    foreach ($user_storage->loadMultiple($ids) as $user) {
      /** @var \Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem $item */
      $item = $user->organization->getOrganizationItem($organization, FALSE);
      $memberships[$item->status][$item->getEntity()->id()] = $item;
    }

    $build = [];
    foreach ($memberships as $status => $items) {
      $status_label = ucfirst($status);

      $build[$status] = [
        '#type' => 'table',
        '#title' => $this->t('@status Members', ['@status' => $status_label]),
        '#header' => [
          $this->t('Name'),
          $this->t('Role'),
          $this->t('Operations'),
        ],
        '#empty' => $this->t(
          '@organization has no @status members.',
          [
            '@organization' => $organization->label(),
            '@status' => $status_label,
          ]
        ),
        '#rows' => [],
      ];

      foreach ($items as $item) {
        $build[$status]['#rows'][$item->getEntity()->id()] = [
          'name' => $item->getEntity()->label(),
          'role' => ucfirst($item->role),
          'operations' => [
            'data' => $this->getOrganizationItemOperations($item, $status),
          ],
        ];
      }
    }

    return $build;
  }

  /**
   * Get the operations.
   *
   * @param \Drupal\organization\Plugin\Field\FieldType\OrganizationMetadataReferenceItem $item
   * @param string $status
   *
   * @return array
   */
  protected function getOrganizationItemOperations(OrganizationMetadataReferenceItem $item, string $status) {
    $build = [
      '#type' => 'operations',
      '#links' => [],
    ];

    if ($status === OrganizationMetadataReferenceItem::STATUS_REQUESTED) {
      $build['#links']['approve'] = [
        'title' => $this->t('Approve'),
        'weight' => 0,
        'url' => Url::fromRoute(
          'job_board.recruiter.team.request.approve',
          [
            'organization' => $item->entity->id(),
            'user' => $item->getEntity()->id(),
          ]
        )
      ];
      $build['#links']['reject'] = [
        'title' => $this->t('Reject'),
        'weight' => 1,
        'url' => Url::fromRoute(
          'job_board.recruiter.team.request.reject',
          [
            'organization' => $item->entity->id(),
            'user' => $item->getEntity()->id(),
          ]
        )
      ];
    }
    if ($status === OrganizationMetadataReferenceItem::STATUS_ACTIVE) {
      $build['#links']['change_role'] = [
        'title' => $this->t('Change Role'),
        'weight' => 1,
        'url' => Url::fromRoute(
          'job_board.recruiter.team.change_role',
          [
            'organization' => $item->entity->id(),
            'user' => $item->getEntity()->id(),
          ]
        )
      ];
    }

    return $build;
  }

}
