<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 10/10/2018
 * Time: 18:57
 */

namespace Drupal\job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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

    $structured_data = [
      '@context' => "http://schema.org/",
      '@type' => "JobPosting",
      'title' => (string) $job->label(),
      'description' => $job->description->render(),
      'datePosted' => $job->publish_date->date->format('Y-m-d'),
      'validThrough' => $job->end_date->date->format('Y-m-d\T00:00'),
    ];

    return [];
  }
}
