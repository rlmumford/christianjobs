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
<div class="col col-md-offset-3 col-md-9 offset-4">
<h2>Are you passionate about impacting the next generation through education?</h2>

<p><span>On behalf of Luova Education Group and Victory Christian International School, Christian Jobs are actively recruiting teachers to join an exciting new school program in Busan, South Korea.</span></p>
<a class="btn" href="https://www.christianjobs.co.uk/jobs/christian-jobs-ltd/1333">Find out more</a></div>
</div>';

    return $content;
  }
}
