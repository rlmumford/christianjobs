/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings, Drupal) {
  'use strict';

  Drupal.behaviors.fmcg_material_radios = {
    attach: function (context) {
      $('input.account-type-radios').parent().addClass('btn');
      $('input.form-radio').each(function() {
        if ($(this).is(':checked')) {
          $(this).parent().addClass('form-type-radio-checked');
        }
      });
      $('input.form-radio').change(function () {
        if ($(this).is(':checked')) {
          $('input.form-radio[name="'+$(this).attr('name')+'"]').parent().removeClass('form-type-radio-checked');
          $(this).parent().addClass('form-type-radio-checked');
        }
        else {
          $(this).parent().removeClass('form-type-radio-checked');
        }
      });
    }
  };

})(jQuery, drupalSettings, Drupal);
