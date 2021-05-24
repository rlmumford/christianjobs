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
    $content['#markup'] = '<div class="row lcm-banner-container">
<div class="col col-xs-12 col-md-3 first-md lcm-logo-wrapper">
<img src="/'.drupal_get_path('theme', 'cj_material').'/assets/lcmlogo.png" class="lcm-banner-logo" />
</div>
<div class="col col-xs-12 col-md-9 lcm-banner-text">
<h2>Are you ready for a new opportunity in London?</h2>

<p><span>Christian Jobs are currently recruiting for London City Mission, a London-focused Christian organisation equipping missionaries to reach the streets of London who are looking to expand their team through a number of opportunities. If you or someone you know are interested in one of these new and exciting opportunities, contact our recruitment team to find out more!</span></p>
<a class="btn" href="https://www.christianjobs.co.uk/employer/54">Find out more</a></div>
</div>';
    return $content;
  }
}
