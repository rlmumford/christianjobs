<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 10/10/2018
 * Time: 18:57
 */

namespace Drupal\job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;
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
      'validThrough' => $job->end_date->date->format('Y-m-d\T00:00'),
      'identifier' => [
        '@type' => 'PropertyValue',
        'name' => 'ChristianJobs.co.uk',
        'value' => $job->id(),
      ],
      'jobLocation' => [
        '@type' => 'Place',
        'address' => [
          '@type' => 'PostalAddress',
          'addressCountry' => $job->location->country_code,
          'addressRegion' => $job->location->administrative_area,
          'addressLocality' => $job->location->locality,
        ],
      ],
    ];

    if (!$job->industry->isEmpty()) {
      $structured_data['industry'] = $job->industry->entity->label();
    }

    // Hiring organisation
    if (!$job->organisation->isEmpty()) {
      /** @var ProfileStorageInterface $profile_storage */
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
      $profile = $profile_storage->loadDefaultByUser($job->organisation->entity, 'employer');

      if ($profile) {
        if ($profile->logo->isEmpty()) {
          $structured_data['image'] = $profile->logo->entity->url();
        }
        $organisation = array_filter([
          '@type' => 'Organization',
          'name' => $profile->employer_name->value,
          'email' => $profile->email->value,
          'telephone' => $profile->tel->value,
          'logo' => $profile->logo->isEmpty() ? NULL : $profile->logo->entity->url(),
        ]);
        if (!$profile->address->isEmpty()) {
          $organisation['address'] = array_filter([
            '@type' => 'PostalAddress',
            'addressCountry' => $profile->address->country_code,
            'streetAddress' => $profile->address->address_line1,
            'addressLocality' => $profile->address->address_line2,
            'addressRegion' => $profile->address->administrative_area,
            'postalCode' => $profile->address->postal_code,
          ]);
        }
        $structured_data['hiringOrganization'] = $organisation;
      }
    }

    // Compensation
    if (!$job->compensation->isEmpty() || !$job->hours->isEmpty()) {
      $key = $job->compensation->value;
      if (!$key || $key == 'salaried' || $key == 'pro_rate') {
        $key = !$job->hours->isEmpty() ? $job->hours->value : 'other';
      }

      $compensation_map = [
        'part_time' => 'PART_TIME',
        'full_time' => 'FULL_TIME',
        'volunteer' => 'VOLUNTEER',
        'zero_hours' => 'TEMPORARY',
        'flexible' => 'PART_TIME',
        'apprentice' => 'OTHER',
        'other' => 'OTHER',
      ];
      $structured_data['employmentType'] = $compensation_map[$key];
    }

    // Base Salary data.
    if (!$job->salary->isEmpty()) {
      $min = $job->salary->from;
      $max = $job->salary->to;

      $base_salary = [
        '@type' => 'MonetaryAmount',
        'currency' => 'GBP',
        'value' => [
          '@type' => 'QuantitativeValue',
          'unitText' => 'YEAR',
        ]
      ];

      if ($min == $max || empty($max)) {
        $base_salary['value']['value'] = $min;
      }
      else {
        $base_salary['value']['minValue'] = $min;
        $base_salary['value']['maxValue'] = $max;
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
