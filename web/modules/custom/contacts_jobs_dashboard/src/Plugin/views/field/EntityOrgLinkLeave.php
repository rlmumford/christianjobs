<?php

namespace Drupal\contacts_jobs_dashboard\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a leave link to an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("contacts_jobs_dashboard_org_leave")
 */
class EntityOrgLinkLeave extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $entity = $this->getEntity($row);
    $options = ['query' => $this->getDestinationArray()];
    return Url::fromRoute('entity.group.leave', ['group' => $entity->id()], $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Leave');
  }

}
