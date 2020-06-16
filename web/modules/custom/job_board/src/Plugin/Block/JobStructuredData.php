<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 10/10/2018
 * Time: 18:57
 */

namespace Drupal\job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\job_role\Plugin\Field\FieldType\SalaryItem;
use Drupal\profile\ProfileStorageInterface;

/**
 * Class JobStructuredData
 *
 * @Block(
 *   id = "job_role_structured_data",
 *   admin_label = @Translation("Job Structured Data"),
 *   context = {
 *     "job" = @ContextDefinition("entity:job_role", label = @Translation("Job"))
 *   }
 * )
 */
class JobStructuredData extends BlockBase {

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $job = $this->getContextValue('job');

    // @todo seperate title and label?
    $structured_data = [
      '@context' => "http://schema.org/",
      '@type' => "JobPosting",
      'title' => (string) $job->label(),
      'name' => (string) $job->label(),
      'description' => check_markup($job->description->value, $job->description->format),
      'datePosted' => $job->publish_date->date->format('Y-m-d'),
      'identifier' => [
        '@type' => 'PropertyValue',
        'name' => 'ChristianJobs.co.uk',
        'value' => $job->id(),
      ],
      'jobLocation' => [
        '@type' => 'Place',
        'address' => [
          '@type' => 'PostalAddress',
          'addressCountry' => $job->locations->entity->address->country_code,
          'addressRegion' => $job->locations->entity->address->administrative_area,
          'addressLocality' => $job->locations->entity->address->locality,
        ],
      ],
    ];

    if (!$job->end_date->isEmpty()) {
      $structured_data['validThrough'] = $job->end_date->date->format('Y-m-d\T00:00');
    }
    if (!$job->industry->isEmpty()) {
      $structured_data['industry'] = $job->industry->entity->label();
    }

    // Hiring organisation
    if (!$job->organization->isEmpty()) {
      $org = $job->organization->entity;
        if (!$org->logo->isEmpty()) {
          $structured_data['image'] = $org->logo->entity->url();
        }
        $organisation = array_filter([
          '@type' => 'Organization',
          'name' => $org->name->value,
          'email' => $org->email->value,
          'telephone' => $org->tel->value,
          'logo' => $org->logo->isEmpty() ? NULL : $org->logo->entity->url(),
        ]);
        if (!$org->headquarters->isEmpty() && !$org->headquarters->entity->address->isEmpty()) {
          $hq = $org->headquarters->entity;

          $organisation['address'] = array_filter([
            '@type' => 'PostalAddress',
            'addressCountry' => $hq->address->country_code,
            'streetAddress' => $hq->address->address_line1,
            'addressLocality' => $hq->address->address_line2,
            'addressRegion' => $hq->address->administrative_area,
            'postalCode' => $hq->address->postal_code,
          ]);
        }
        $structured_data['hiringOrganization'] = $organisation;
    }

    // Compensation
    if (!$job->hours->isEmpty()) {
      $key = $job->hours->value;
      $compensation_map = [
        'part_time' => 'PART_TIME',
        'full_time' => 'FULL_TIME',
        'zero_hours' => 'TEMPORARY',
        'flexible' => 'PART_TIME',
        'apprentice' => 'OTHER',
        'other' => 'OTHER',
      ];
      $structured_data['employmentType'] = $compensation_map[$key];
    }

    // Base Salary data.
    if (!$job->pay->isEmpty()) {
      $pay = $job->pay->get(0);

      $unit_map = [
        SalaryItem::TYPE_PA => 'YEAR',
        SalaryItem::TYPE_PM => 'MONTH',
        SalaryItem::TYPE_PW => 'WEEK',
        SalaryItem::TYPE_PD => 'DAY',
        SalaryItem::TYPE_PH => 'HOUR',
      ];

      $base_salary = [
        '@type' => 'MonetaryAmount',
        'currency' => $pay->currency_code,
        'value' => [
          '@type' => 'QuantitativeValue',
          'unitText' => $unit_map[$pay->type],
        ]
      ];

      if ($pay->min == $pay->max || empty($pay->max)) {
        $base_salary['value']['value'] = $pay->min;
      }
      else {
        $base_salary['value']['minValue'] = $pay->min;
        $base_salary['value']['maxValue'] = $pay->max;
      }

      $structured_data['baseSalary'] = $base_salary;
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [
        'type' => 'application/ld+json',
      ],
      '#value' => json_encode($structured_data),
    ];
  }
}
