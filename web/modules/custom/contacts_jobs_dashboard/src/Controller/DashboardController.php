<?php

namespace Drupal\contacts_jobs_dashboard\Controller;

use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\contacts_group\Plugin\Block\ContactOrgRelationshipFormBlock;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The jobs dashboard controller.
 */
class DashboardController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new DirectoryController object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityFormBuilder $entity_form_builder, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
  }

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

  /**
   * Title callback for the organisation edit form.
   *
   * @param \Drupal\user\UserInterface $user
   *   The organisation user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function editTitle(UserInterface $user) {
    return $this->t('Update @label', ['@label' => $user->label()]);
  }

  /**
   * Title callback for the organisation edit form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The organisation user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function leaveTitle(GroupInterface $group) {
    return $this->t('Leave @label', ['@label' => $group->label()]);
  }

  /**
   * Title callback for the organisation edit form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The organisation user.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The organisation user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function removeTitle(GroupInterface $group, GroupContentInterface $group_content) {
    return $this->t('Remove @content from @group', [
      '@group' => $group->label(),
      '@content' => $group_content->label(),
    ]);
  }

  /**
   * Title callback for the organisation join form.
   *
   * @param \Drupal\user\UserInterface $user
   *   The organisation user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function joinTitle(UserInterface $user) {
    return $this->t('Join @label', ['@label' => $user->label()]);
  }

  /**
   * Title callback for the manage organisation page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The organisation user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function teamTitle(UserInterface $user) {
    return $this->t('Team for @label', ['@label' => $user->label()]);
  }

  /**
   * Title callback for the manage jobs page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The organisation user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function jobsTitle(UserInterface $user) {
    return $this->t('Jobs for @label', ['@label' => $user->label()]);
  }

  /**
   * Page for selecting existing organisations to edit.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either a render array for the page or, if the current user has no
   *   organisations, a redirect to the register page.
   */
  public function existingOrgs() {
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());
    /** @var \Drupal\contacts_group\Plugin\Field\GroupMembershipItemList $organisations */
    $organisations = $user->organisations;

    // If there are none, redirect to the register form.
    if (count($organisations) === 0) {
      // @fixme Add page for organisation adding.
    }
    $content = [];

    $content['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'h6',
      '#value' => $this->t('You are already related to the following organisations, do you want to edit one of these instead of creating a new one?'),
    ];

    // Build our table.
    $content['orgs'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Organisation'),
        $this->t('Type'),
        $this->t('Job title'),
        '',
      ],
      '#rows' => [],
    ];

    // Add each organisation tothe table.
    foreach ($organisations as $item) {
      /** @var \Drupal\group\Entity\GroupContentInterface $membership */
      $membership = $item->entity;
      $organisation = $membership->getGroup();

      // Get our role optionss.
      $provider = $membership->indiv_role->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getOptionsProvider('value', $membership);
      $options = OptGroup::flattenOptions($provider->getPossibleOptions());

      // Add our row.
      $content['orgs']['#rows'][] = [
        $organisation->label(),
        $options[$membership->indiv_role->value],
        $membership->job_title->value,
        [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('contacts_jobs_dashboard.edit', ['user' => $organisation->contacts_org->target_id]),
              ],
              'leave' => [
                'title' => $this->t('Leave'),
                'url' => Url::fromRoute('<front>'),
              ],
            ],
          ],
        ],
      ];
    }

    // Add a link to register a new organisation.
    // @fixme Add a registration route link.
    $content['register'] = [
      '#type' => 'link',
      '#title' => $this->t('No, I want to register a different organisation.'),
      '#url' => Url::fromRoute('user.logout', [], [
        'query' => ['skip-existing' => TRUE],
      ]),
      '#attributes' => [
        'class' => ['button', 'button--primary', 'modal-dismiss'],
      ],
    ];

    return $content;
  }

  /**
   * Provides a form for joining an organisation.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to add the group content to.
   *
   * @return array
   *   A group submission form.
   */
  public function joinOrganisationForm(AccountInterface $user) {
    $plugin_id = 'group_membership';

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $user->get('group')->entity;
    if (!$group) {
      throw new NotFoundHttpException('User is not an organisation.');
    }

    $existing = $group->getContentByEntityId($plugin_id, $this->currentUser->id());
    if ($existing) {
      throw new AccessDeniedHttpException('User is already a member of the Group.');
    }

    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
    $values = [
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $this->currentUser->id(),
    ];
    $group_content = $this->entityTypeManager()->getStorage('group_content')->create($values);

    // Only allow organisations to be joined.
    $form_state_additions = ['organisation_roles' => ['crm_org']];

    return $this->entityFormBuilder->getForm($group_content, 'contacts-org', $form_state_additions);
  }

  /**
   * Allow group adminstrators to manage group members.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user requesting access.
   *
   * @return array
   *   A group team members page.
   */
  public function organisationTeamPage(AccountInterface $user) {
    $content = [];

    $content['help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('This page is for managing the members of your team. You can invite new team members and adjust the roles of existing team members as well as accept or reject join requests.'),
    ];

    $pending_members_view_block = \Drupal::service('plugin.manager.block')->createInstance('views_block:contacts_orgs_manage-pending_member_indivs');

    $block_content = $pending_members_view_block->build();
    $content['pending_members'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $pending_members_view_block->getConfiguration(),
      '#plugin_id' => $pending_members_view_block->getPluginId(),
      '#base_plugin_id' => $pending_members_view_block->getBaseId(),
      '#derivative_plugin_id' => $pending_members_view_block->getDerivativeId(),
      '#weight' => $pending_members_view_block->getConfiguration()['weight'] ?? 0,
      'content' => $block_content,
    ];

    $members_view_block = \Drupal::service('plugin.manager.block')->createInstance('views_block:contacts_orgs_manage-member_indivs');

    $block_content = $members_view_block->build();
    $content['members'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $members_view_block->getConfiguration(),
      '#plugin_id' => $members_view_block->getPluginId(),
      '#base_plugin_id' => $members_view_block->getBaseId(),
      '#derivative_plugin_id' => $members_view_block->getDerivativeId(),
      '#weight' => $members_view_block->getConfiguration()['weight'] ?? 1,
      'content' => $block_content,
    ];

    $members_form_conf = [
      'query_key' => 'member',
      'provides' => ContactOrgRelationshipFormBlock::PROVIDES_GROUP,
      'member_roles' => [
        'crm_indiv',
      ],
      'organisation_roles' => [
        'crm_org',
      ],
    ];
    /** @var \Drupal\Core\Block\BlockPluginInterface $members_form_block */
    $members_form_block = \Drupal::service('plugin.manager.block')->createInstance('contacts_org_relationship_form', $members_form_conf);

    // Build our contexts.
    if ($members_form_block instanceof ContextAwarePluginInterface) {
      $contexts = [
        'user' => new Context(new ContextDefinition('entity:user'), $user),
      ];

      // Apply the contexts to the block.
      \Drupal::service('context.handler')->applyContextMapping($members_form_block, $contexts);
    }

    $block_content = $members_form_block->build();

    // @todo Clean up/simplify title building.
    $block_content['#title'] = $members_form_block->label();
    $members_form_block_conf = $members_form_block->getConfiguration();
    $relationship = \Drupal::request()->query->get($members_form_block_conf['query_key']);
    if ($relationship && $relationship == 'add') {
      $block_content['#title'] = $this->t('Add a team member');
    }

    $block_content['add']['#title'] = $this->t('Add team member');
    $block_content['add']['#attributes']['class'] = [
      'btn',
      'btn-primary',
      'my-3',
    ];
    $content['member_form'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $members_form_block->getConfiguration(),
      '#plugin_id' => $members_form_block->getPluginId(),
      '#base_plugin_id' => $members_form_block->getBaseId(),
      '#derivative_plugin_id' => $members_form_block->getDerivativeId(),
      '#weight' => $members_form_block->getConfiguration()['weight'] ?? 2,
      'content' => $block_content,
    ];

    return $content;
  }

  /**
   * Allow group recruiters to manage group jobs.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user requesting access.
   *
   * @return array
   *   A group jobs page.
   */
  public function organisationJobsPage(AccountInterface $user) {
    $content = [];

    $destination = \Drupal::destination()->getAsArray();
    $url_options = [
      'query' => $destination,
    ];

    $params = [
      'organisation' => $user->id(),
    ];
    $content['jobs_add'] = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => 'Add Job',
        'url' => Url::fromRoute('entity.contacts_job.post_form', $params),
      ],
    ];

    $jobs_block = \Drupal::service('plugin.manager.block')->createInstance('views_block:contacts_jobs_manage-org_jobs');
    $block_content = $jobs_block->build();
    $content['jobs'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $jobs_block->getConfiguration(),
      '#plugin_id' => $jobs_block->getPluginId(),
      '#base_plugin_id' => $jobs_block->getBaseId(),
      '#derivative_plugin_id' => $jobs_block->getDerivativeId(),
      '#weight' => $jobs_block->getConfiguration()['weight'] ?? 0,
      'content' => $block_content,
    ];

    $content['cj_app_settings_add'] = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => 'Add Application Setting',
        'url' => Url::fromRoute('entity.cj_app_settings.add_form', [
          'user' => $user->id(),
        ], $url_options),
      ],
    ];

    $cj_app_settings_block = \Drupal::service('plugin.manager.block')->createInstance('views_block:contacts_jobs_app_settings_manage-block_manage');
    $settings_block_content = $cj_app_settings_block->build();
    $content['cj_app_settings'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $cj_app_settings_block->getConfiguration(),
      '#plugin_id' => $cj_app_settings_block->getPluginId(),
      '#base_plugin_id' => $cj_app_settings_block->getBaseId(),
      '#derivative_plugin_id' => $cj_app_settings_block->getDerivativeId(),
      '#weight' => $cj_app_settings_block->getConfiguration()['weight'] ?? 0,
      'content' => $settings_block_content,
    ];

    return $content;
  }

  /**
   * Allow user to see their own organisations.
   *
   * @todo Move this to another controller.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user requesting access.
   *
   * @return array
   *   A group submission form.
   */
  public function myOrganisationsPage(AccountInterface $user) {
    $content = [];

    $parent_org_view_block = \Drupal::service('plugin.manager.block')->createInstance('views_block:contacts_orgs_user_dash-orgs');

    $block_content = $parent_org_view_block->build();
    $content['parents'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $parent_org_view_block->getConfiguration(),
      '#plugin_id' => $parent_org_view_block->getPluginId(),
      '#base_plugin_id' => $parent_org_view_block->getBaseId(),
      '#derivative_plugin_id' => $parent_org_view_block->getDerivativeId(),
      '#weight' => $parent_org_view_block->getConfiguration()['weight'] ?? 0,
      'content' => $block_content,
    ];

    $content['help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('If you would like to be able to edit your organisation, please contact email contact@fmcgjobs.com.'),
    ];

    $parent_org_form_conf = [
      'query_key' => 'org',
      'provides' => ContactOrgRelationshipFormBlock::PROVIDES_CONTENT,
      'member_roles' => [
        'crm_indiv',
      ],
    ];
    $parent_org_form_block = \Drupal::service('plugin.manager.block')->createInstance('contacts_org_relationship_form', $parent_org_form_conf);

    // Build our contexts.
    if ($parent_org_form_block instanceof ContextAwarePluginInterface) {
      $contexts = [
        'user' => new Context(new ContextDefinition('entity:user'), $user),
      ];

      // Apply the contexts to the block.
      \Drupal::service('context.handler')->applyContextMapping($parent_org_form_block, $contexts);
    }

    $block_content = $parent_org_form_block->build();
    $block_content['#title'] = $this->t('Join an Organisation');
    $block_content['add']['#title'] = $this->t('Join organisation');
    $block_content['add']['#attributes']['class'] = [
      'btn',
      'btn-primary',
      'my-3',
    ];
    $content['parent_form'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $parent_org_form_block->getConfiguration(),
      '#plugin_id' => $parent_org_form_block->getPluginId(),
      '#base_plugin_id' => $parent_org_form_block->getBaseId(),
      '#derivative_plugin_id' => $parent_org_form_block->getDerivativeId(),
      '#weight' => $parent_org_form_block->getConfiguration()['weight'] ?? 0,
      'content' => $block_content,
    ];

    $content['create_parent'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t("If you can't find your organisation with the link above, click the link below to register a new one."),
      '#prefix' => '<h2>Register a new Organisation</h2>',
    ];

    $content['create_org_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Register New Organisation'),
      '#url' => Url::fromRoute('contacts_jobs_dashboard.recruiter_organisation.register'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'my-3']],
    ];

    return $content;
  }

  /**
   * Allow user to switch between organisations in a modal.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user requesting org switch.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Open modal ajax response.
   */
  public function switchOrganisation(AccountInterface $user) {
    $content = [];

    $block_manager = \Drupal::service('plugin.manager.block');
    $parent_org_view_block = $block_manager->createInstance('views_block:contacts_orgs_user_dash-orgs_modal');
    $block_content = $parent_org_view_block->build();
    $content['orgs'] = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $parent_org_view_block->getConfiguration(),
      '#plugin_id' => $parent_org_view_block->getPluginId(),
      '#base_plugin_id' => $parent_org_view_block->getBaseId(),
      '#derivative_plugin_id' => $parent_org_view_block->getDerivativeId(),
      '#weight' => $parent_org_view_block->getConfiguration()['weight'] ?? 0,
      'content' => $block_content,
    ];

    $options = [
      'width' => '60%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand('Current Organizations/Divisions', $content, $options));

    return $response;
  }

}
