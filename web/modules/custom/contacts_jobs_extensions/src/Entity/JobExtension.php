<?php

namespace Drupal\contacts_jobs_extensions\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Job Role Extension Entity.
 *
 * @ContentEntityType(
 *   id = "cj_extension",
 *   label = @Translation("Job Extension"),
 *   label_singular = @Translation("job extension"),
 *   label_plural = @Translation("job extensions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count job extension",
 *     plural = "@count job extensions"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\contacts_jobs_extensions\JobExtensionStorage",
 *     "access" = "Drupal\contacts_jobs_extensions\JobExtensionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "cj_extension",
 *   revision_table = "cj_extension_revision",
 *   admin_permission = "administer job extensions",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "owner" = "owner",
 *   }
 * )
 */
class JobExtension extends ContentEntityBase implements PurchasableEntityInterface, EntityOwnerInterface {
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if ($this->product->entity) {
      $this->duration = $this->product->entity->extension_duration->value;
    }

    if ($this->paid->value && !$this->applied->value) {
      $this->applied = TRUE;

      /** @var \Drupal\contacts_jobs\Entity\Job $job */
      $job = $this->job->entity;

      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $publish_end = clone $job->publish_end->date;
      $today_date = new DrupalDateTime();
      if ($today_date > $publish_end) {
        $publish_end = $today_date;
      }
      $publish_end->add(new \DateInterval($this->duration->value ?: 'P30D'));
      $job->publish_end->value = $publish_end->format('U');

      $job->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $duration = ($this->duration->value == 'P30D') ? "30 day" : "60 day";
    return $duration." extension of ".$this->job->entity->label();
  }

  /**
   * Gets the stores through which the purchasable entity is sold.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface[]
   *   The stores.
   */
  public function getStores() {
    return $this->entityTypeManager()->getStorage('commerce_store')->loadMultiple();
  }

  /**
   * Gets the purchasable entity's order item type ID.
   *
   * Used for finding/creating the appropriate order item when purchasing a
   * product (adding it to an order).
   *
   * @return string
   *   The order item type ID.
   */
  public function getOrderItemTypeId() {
    return 'contacts_jobs_extensions_job_extension';
  }

  /**
   * Gets the purchasable entity's order item title.
   *
   * Saved in the $order_item->title field to protect the order items of
   * completed orders against changes in the referenced purchased entity.
   *
   * @return string
   *   The order item title.
   */
  public function getOrderItemTitle() {
    $duration = ($this->duration->value == 'P30D') ? "30 day" : "60 day";
    return $duration." extension of ".$this->job->entity->label();
  }

  /**
   * Gets the purchasable entity's price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The price, or NULL.
   */
  public function getPrice() {
    /** @var \Drupal\commerce_product\Entity\ProductVariation $variation */
    $variation = $this->product->entity;
    return $variation->getPrice();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['job'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Job'))
      ->setDescription(new TranslatableMarkup('The job that this extension is extending.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'contacts_job')
      ->setSetting('handler', 'default');

    $fields['product'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Product'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'commerce_product_variation')
      ->setSetting('handler_settings', [
        'target_bundles' => ['job_extension' => 'job_extension'],
      ])
      ->setSetting('handler', 'default');

    $fields['duration'] = BaseFieldDefinition::create('list_string')
      ->setSetting('allowed_values', [
        'P30D' => new TranslatableMarkup('30 Days'),
        'P60D' => new TranslatableMarkup('60 Days'),
      ])
      ->setLabel(new TranslatableMarkup('Duration'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['paid'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Paid'))
      ->setDescription(new TranslatableMarkup('Has this job extension been paid for.'))
      ->setSetting('on_label', t('Paid'))
      ->setSetting('off_label', t('Unpaid'))
      ->setCardinality(1)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['applied'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Paid'))
      ->setDescription(new TranslatableMarkup('Has this job extension been applied to the job.'))
      ->setSetting('on_label', t('Applied'))
      ->setSetting('off_label', t('Not Applied'))
      ->setCardinality(1)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Owner'))
      ->setDescription(new TranslatableMarkup('The user that owns this extension.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time when the job_role was created.'))
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time when the job_role was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }
}
