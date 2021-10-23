<?php

namespace Drupal\cj\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block that displays page content.
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
    $content['#markup'] = '<div class="row ww-banner-container">
<div class="col col-xs-12 col-md-3 first-md ww-logo-wrapper">
<img src="/'.drupal_get_path('theme', 'cj_material').'/assets/wwlogo.png" class="ww-banner-logo" />
</div>
<div class="col col-xs-12 col-md-9 ww-banner-text">
<h2>Christian Graduate Scheme in Business</h2>

<p><span class="ww-highlight">Worship.Works</span> trains followers of Jesus to see their work as worship, with endless possibilities. Their vision is to equip thousands of people to minister through their work by offering teaching, speaking and publishing online resources. They are looking for people to join their <span class="ww-highlight">Christian Graduate Internship in Workplace Ministry</span>. If you or someone you know may be interested in this opportunity then contact our recruitment team today!</span></span></p>
<a class="btn" href="https://www.christianjobs.co.uk/jobs/worshipworks/2783">Find out more</a></div>
</div>';
    return $content;
  }
}
