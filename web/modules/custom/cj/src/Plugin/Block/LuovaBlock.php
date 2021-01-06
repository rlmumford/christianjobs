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
    $content['#markup'] = '<div class="row ml-24">
<div class="col col-xs-12 col-md-2 first-md luova-logo-wrapper">
<img src="/'.drupal_get_path('theme', 'cj_material').'/assets/lcmlogo.png" class="luova-banner-logo mt-24" />
</div>
<div class="col col-xs-12 col-md-9">
<h2>Are you ready for a new fundraising opportunity?</h2>

<p><span>Christian Jobs are currently recruiting for London City Mission, a London-focused Christian organisation equipping missionaries to reach the streets of London who are looking to expand their fundraising team through a number of opportunities. If you are a fundraiser, or know someone who may be interested in one of these new and exciting opportunities, contact our recruitment team to find out more!</span></p>
<a class="btn" href="https://www.christianjobs.co.uk/jobs?label=&organisation=London+City+Mission">Find out more</a></div>
</div>';
    return $content;
  }
}
