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
    $content['#markup'] = '<div class="row hfg-banner-container">
<div class="col col-xs-12 col-md-3 first-md hfg-logo-wrapper">
<img src="/'.drupal_get_path('theme', 'cj_material').'/assets/hfglogo.png" class="hfg-banner-logo" />
</div>
<div class="col col-xs-12 col-md-9 hfg-banner-text">
<h2>Would you like to use your operational leadership skills to serve a Christian charity?</h2>

<p><span>Christian Jobs are currently recruiting for <span class="hfg-highlight">Home For Good</span>, who work to mobilise the Church in the UK. Home For Good respond to the needs of vulnerable children through families stepping forward to foster or adopt and churches wrapping around families with support. They are looking for a new <span class="hfg-highlight">Director Of Finance & Infrastructure</span>, if you or someone you know may be interested in this role then contact our recruitment team today!</span></span></p>
<a class="btn" href="https://www.christianjobs.co.uk/jobs/home-good/2672">Find out more</a></div>
</div>';
    return $content;
  }
}
