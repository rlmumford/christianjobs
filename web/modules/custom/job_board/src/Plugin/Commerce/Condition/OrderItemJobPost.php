<?php

namespace Drupal\job_board\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides commerce condition for Job Post items.
 *
 * @CommerceCondition(
 *   id = "order_item_job_post",
 *   label = @Translation("Job Role"),
 *   display_label = @Translation("Job Post"),
 *   category = @Translation("Job Board"),
 *   entity_type = "commerce_order_item",
 *   weight = -1,
 * )
 */
class OrderItemJobPost extends ConditionBase {

  const TYPE_STANDARD = 'standard';
  const TYPE_EXTENDED = 'extended';
  const TYPE_RPO = 'rpo';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Job Types'),
      '#options' => [
        static::TYPE_STANDARD => $this->t('Standard Post'),
        static::TYPE_EXTENDED => $this->t('Extended Post'),
        static::TYPE_RPO => $this->t('RPO'),
      ],
      '#default_value' => $this->getJobTypes(),
    ];

    $form['max_jobs'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum No. Jobs'),
      '#description' => $this->t('Only apply this promotion to the first N items.'),
      '#default_value' => $this->getMaxApplications(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['types'] = $values['types'];
    $this->configuration['max_jobs'] = $values['max_jobs'];
  }

  /**
   * Evaluates the condition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\job_board\JobBoardJobRole $job */
    $job = $order_item->getPurchasedEntity();
    $order = $order_item->getOrder();

    if (!$job || $job->getEntityTypeID() != 'job_role') {
      return FALSE;
    }

    $used = 0;
    foreach ($order->getItems() as $item) {
      foreach ($item->getAdjustments() as $adjustment) {
        if ($adjustment->getType() == 'promotion') {
          // @todo: Find out a way to check whether this is the same promotion as the one being tested.
          // @todo: Patch commerce OrderItemPromotionOfferBase to use ParentEntityAwareInterface and set the offer on the condition class.
          $used++;
          break;
        }
      }
    }
    if ($used >= $this->getMaxApplications()) {
      return FALSE;
    }

    $job_type = static::TYPE_STANDARD;
    if ($job->initial_duration->value == 'P60D') {
      $job_type = static::TYPE_EXTENDED;
    }
    if ($job->rpo->value) {
      $job_type = static::TYPE_RPO;
    }

    return in_array($job_type, $this->getJobTypes());
  }

  /**
   * Get the job types supported by this condition.
   *
   * @return array
   *   The types of jobs supported.
   */
  protected function getJobTypes() {
    $configuration = $this->getConfiguration();
    return isset($configuration['types']) ? array_filter($configuration['types']) : [];
  }

  /**
   * Get the maximum number of applications
   *
   * @return int
   */
  protected function getMaxApplications() {
    $configuration = $this->getConfiguration();
    return isset($configuration['max_jobs']) ? $configuration['max_jobs'] : 0;
  }
}
