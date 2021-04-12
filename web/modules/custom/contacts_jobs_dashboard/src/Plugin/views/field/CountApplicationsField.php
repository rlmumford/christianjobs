<?php

namespace Drupal\contacts_jobs_dashboard\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a count of number of submitted/received applications to jobs.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("contacts_jobs_dashboard_count_submitted_applications")
 */
class CountApplicationsField extends FieldPluginBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Total number of submitted applications, keyed by job id.
   *
   * @var array
   */
  protected $applicationCounts = [];

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Organisation field alias.
   *
   * @var string
   */
  protected $orgFieldAlias;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $self */
    $self = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $self->db = $container->get('database');
    $self->entityTypeManager = $container->get('entity_type.manager');
    return $self;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
    // Base class will have already added in the job id field.
    // We need to also ensure the organisation field is included as we need
    // this for generating the link.
    $this->orgFieldAlias = $this->query->addField($this->tableAlias, 'organisation');
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $job_ids = array_map(function ($value) {
      return $this->getValue($value);
    }, $values);

    if (count($job_ids)) {
      $query = $this->db->select('cj_app');
      $query->condition('state', ['submitted', 'received', 'external_ats'], 'IN');
      $query->condition('job', $job_ids, 'IN');
      $query->groupBy('job');
      $query->addExpression('COUNT(1)', 'application_count');
      $query->addField('cj_app', 'job');
      $results = $query->execute()->fetchAll();

      foreach ($results as $result) {
        $this->applicationCounts[$result->job] = $result->application_count;
      }
    }

    parent::preRender($values);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $job_id = $this->getValue($values);
    $org_id = $values->{$this->orgFieldAlias};
    $output = ['#markup' => '0'];

    if (isset($this->applicationCounts[$job_id])) {
      $count = $this->applicationCounts[$job_id];
      $link = Link::createFromRoute($count, 'contacts_jobs_dashboard.user.job.applications', [
        'contacts_job' => $job_id,
        'user' => $org_id,
      ]);
      $output['#markup'] = $link->toString();
    }
    return $output;
  }

}
