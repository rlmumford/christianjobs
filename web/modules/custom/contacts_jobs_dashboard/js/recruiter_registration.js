/**
 * @file
 * Defines Javascript behaviors for the recruiter registration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches behaviours for the find/create organisation selection.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Add the regex state handler and autocomplete close event.
   */
  Drupal.behaviors.organisationFindCreate = {
    attach: function(context, settings) {

      Drupal.states.Dependent.comparisons.Object = function (reference, value) {
        if ('regexp' in reference) {
          return (new RegExp(reference.regexp)).test(value);
        }
      }

      let $name = $(':input[name="crm_org_name[0][value]"]', context);
      $(':input[name="existing_org"]', context)
        .change(function(event) {
          let optionLabel = this.value.replace(/^"?(.+) ((\([0-9]+\))|(\(new\)))"?$/, '$1').trim();
          if (/Create '.+'/.test(optionLabel)) {
            optionLabel = optionLabel.replace(/Create '(.+)'/, '$1');
          }
          $name.val(optionLabel);
        })
        .on('autocompleteopen', function(event, ui) {
          $(this).autocomplete("widget").addClass('ui-autocomplete-with-create');
        })
        .on('autocompleteresponse', function(event, ui) {
          ui.content[ui.content.length - 1].label = '+ ' + ui.content[ui.content.length - 1].label;
        })
        .on('autocompleteclose', function(event, ui) {
          $(event.target).trigger('change');
        });
    }
  }

})(jQuery, Drupal);
