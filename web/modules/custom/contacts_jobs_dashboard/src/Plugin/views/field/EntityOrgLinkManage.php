<?php

namespace Drupal\contacts_jobs_dashboard\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("contacts_jobs_dashboard_org_manage")
 */
class EntityOrgLinkManage extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $entity = $this->getEntity($row);
    return Url::fromRoute('contacts_jobs_dashboard.user.team', ['user' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Manage');
  }

}
