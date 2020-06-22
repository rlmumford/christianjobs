<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block that displays page contemt.
 *
 * @Block(
 *   id = "cj_front_page_luova_block",
 *   admin_label = @Translation("Christian Jobs Front Page Luova Block"),
 * )
 */
class LuovaBlock extends BlockBase {

  /**
   * @inheritDoc
   */
  public function build() {
    $content = [];
    $content['#markup'] = '<div class="row ml-24">
<div class="col col-xs-12 col-md-2 first-md luova-logo-wrapper">
</div>
<div class="col col-xs-12 col-md-9">
<h2>Are you ready for a new teaching opportunity?</h2>

<p><span>Christian Jobs are currently recruiting for Christian teachers in a variety of different roles, and locations. If you can teach, or know someone who may be interested in one of these new and exciting opportunities, contact our recruitment team to find out more!</span></p>
<a class="btn" href="https://www.christianjobs.co.uk/jobs/keyword/teacher">Find out more</a></div>
</div>';

    return $content;
  }
}
