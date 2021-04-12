<?php

namespace Drupal\contacts_jobs_dashboard\Form;

use Drupal\contacts_jobs\Entity\JobInterface;
use Drupal\contacts_jobs\Form\JobForm as BaseJobForm;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Extended job form class.
 */
class JobForm extends BaseJobForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // We add cacheability in ::getEntityFromRouteMatch, so ensure that is added
    // to the form array.
    CacheableMetadata::createFromRenderArray($form)
      ->addCacheableDependency($this->entity)
      ->applyTo($form);
    $form = parent::form($form, $form_state);

    // Hide the organisation if already selected.
    if (isset($form['organisation']) && !$this->entity->get('organisation')->isEmpty()) {
      $form['organisation']['#access'] = FALSE;
    }

    if (isset($form['uid'])) {
      $this->alterUid($form['uid']);
    }

    return $form;
  }

  /**
   * Alter the uid element.
   *
   * @param array $element
   *   The UID element to alter.
   */
  protected function alterUid(array &$element) {
    $org = $this->entity->organisation->entity;

    // Don't show if there's no organisation to limit allowed values by.
    if (!$org) {
      $element['#access'] = FALSE;
      return;
    }

    // Hide the field if we don't have organisation access.
    $result = $this->hasOrganisationAccess($this->entity, 'administer members');
    CacheableMetadata::createFromObject($result)
      ->applyTo($element);
    if (!$result->isAllowed()) {
      $element['#access'] = FALSE;
      return;
    }

    // Replace the autocomplete with a select list of plausible posters.
    //
    // Every member with the 'manage organisation jobs' permission is a
    // potential owner.
    $allowed_values = [];
    /** @var \Drupal\group\GroupMembership $other_member */
    foreach ($org->group->entity->getMembers() as $other_member) {
      if ($other_member->hasPermission('manage organisation jobs')) {
        $allowed_values[$other_member->getUser()->id()] = $other_member->getUser()->label();
      }
    }

    // Always add the current default as an allowed value.
    $current_default = $element['widget'][0]['target_id']['#default_value'];
    if (is_numeric($current_default)) {
      $current_default = $this->entityTypeManager
        ->getStorage('user')
        ->load($current_default);
    }
    if (!isset($allowed_values[$current_default->id()])) {
      $allowed_values[$current_default->id()] = $current_default->label();
    }

    // Don't show the user selection if there is only one allowed value.
    if (count($allowed_values) <= 1) {
      $element['#access'] = FALSE;
      return;
    }

    $select = &$element['widget'][0]['target_id'];
    $select['#type'] = 'select';
    $select['#title'] = $this->t('Recruiter');
    $select['#description'] = $this->t('Which recruiter is responsible for this role?');
    $select['#options'] = $allowed_values;
    $select['#default_value'] = $current_default->id();
    unset($select['#size']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    /** @var \Drupal\contacts_jobs\Entity\JobInterface $entity */
    $entity = parent::getEntityFromRouteMatch($route_match, $entity_type_id);

    // If the entity is not new, we don't want to make any modifications.
    if (!$entity->isNew()) {
      return $entity;
    }

    // See if we have an organisation in the request.
    $params = \Drupal::requestStack()->getCurrentRequest()->query;
    $entity->addCacheContexts(['url.query_args:organisation']);
    if ($params->has('organisation')) {
      $entity->set('organisation', $params->get('organisation'));
      if (!$entity->get('organisation')->entity || !$entity->get('organisation')->entity->hasRole('crm_org')) {
        throw new CacheableNotFoundHttpException($entity, 'Invalid organisation.');
      }

      // Ensure the current user either has administer jobs or permission to
      // this organisation.
      $result = $this->hasOrganisationAccess($entity, 'manage organisation jobs');
      $entity->addCacheableDependency($result);
      if (!$result->isAllowed()) {
        if ($result instanceof AccessResultReasonInterface) {
          throw new CacheableAccessDeniedHttpException($entity, $result->getReason());
        }
        throw new CacheableAccessDeniedHttpException($entity, 'Unable to post jobs for this organisation');
      }
    }
    // If the user has administrative permissions on jobs, allow them to select
    // the organisation in the form. Otherwise, check that the user has a single
    // relationship with permission.
    elseif (!$this->currentUser()->hasPermission('administer job entities') && !$this->currentUser()->hasPermission('edit job entities')) {
      // Cacheability is dependant on the current user.
      $entity->addCacheContexts(['user']);

      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($this->currentUser()->id());

      // If there is not a single organisation, throw a not found as we don't
      // know what to check access on.
      if ($user->get('organisations')->count() !== 1) {
        $entity->addCacheTags(['group_content_list']);
        throw new CacheableNotFoundHttpException($entity, 'Unable to find un-ambiguous organisation');
      }

      /** @var \Drupal\group\GroupMembership $membership */
      $membership = $user->get('organisations')->first()->membership;
      $entity->addCacheableDependency($membership);
      if (!$membership->hasPermission('manage organisation jobs')) {
        throw new CacheableAccessDeniedHttpException($entity, 'Unable to post jobs for this organisation');
      }

      $entity->set('organisation', $membership->getGroup()->get('contacts_org')->target_id);
    }

    return $entity;
  }

  /**
   * Check whether the current user has organisation permissions for the job.
   *
   * @param \Drupal\contacts_jobs\Entity\JobInterface $job
   *   The job.
   * @param string $permission
   *   The group permission to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result of whether the current user has organisation permissions
   *   for the job, including relevant manage job level permissions that would
   *   grant organisation level permissions in the context of the job.
   */
  protected function hasOrganisationAccess(JobInterface $job, string $permission): AccessResultInterface {
    // If the user has permission to manage the job, they will always have full
    // organisation access in the context of the job form.
    $manage_permissions = [
      'administer job entities',
      'edit job entities',
    ];
    $result = AccessResult::allowedIfHasPermissions($this->currentUser(), $manage_permissions, 'OR');

    // If we have these permissions, we don't need to do more granular checks.
    if ($result->isAllowed()) {
      return $result;
    }

    // Everything beyond here is dependent on the job and current user.
    $result->addCacheableDependency($job);
    $result->addCacheContexts(['user']);

    // Otherwise, check the group level permissions.
    $organisation = $job->get('organisation')->entity;
    if (!$organisation) {
      return $result;
    }

    $membership = $organisation->get('group')->entity->getMember($this->currentUser());

    // If there is no membership, cache on the group content list so a new
    // membership invalidates.
    if (!$membership) {
      $result->addCacheTags(['group_content_list']);
      return $result;
    }

    // Check the permission on the group content.
    $result->addCacheableDependency($membership);
    return $result->orIf(AccessResult::allowedIf($membership->hasPermission($permission)));
  }

}
