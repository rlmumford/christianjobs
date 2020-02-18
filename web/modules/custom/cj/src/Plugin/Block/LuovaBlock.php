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
<div class="col col-xs-12 col-md-2 col-md-offset-1 first-md luova-logo-wrapper">
<img src="/'.drupal_get_path('theme', 'cj_material').'/assets/LuovaLogo.png" class="luova-banner-logo mt-24" />
<img src="/'.drupal_get_path('theme', 'cj_material').'/assets/VCISLogo.png" class="luova-banner-logo mt-24" />
</div>
<div class="col col-xs-12 col-md-9">
<h2>Are you passionate about impacting the next generation through education?</h2>

<p><span>In partnership with <span class="luova-education-group">Luova Education Group</span> and <span class="victory-christian-international-school">Victory Christian International School</span> Christian Jobs are actively recruiting teachers to join an exciting new Christian school program in Busan, South Korea.</span></p>
<a class="btn" href="https://www.christianjobs.co.uk/jobs/christian-jobs-ltd/1333">Find out more</a></div>
</div>';

    return $content;
  }
}
