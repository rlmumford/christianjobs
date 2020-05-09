(function ($, drupalSettings) {
  'use strict';

  Drupal.behaviors.select2_fmcg_material = {
    attach: function(context) {
      $(".select2-selection__arrow")
        .addClass("material-icons")
        .html("arrow_drop_down");
    }
  }
})(jQuery, drupalSettings);
