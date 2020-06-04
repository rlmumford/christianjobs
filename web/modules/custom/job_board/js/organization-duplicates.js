(function ($, Drupal) {
  Drupal.behaviors.jobBoardOrganizationDuplicates = {
    attach: function(context) {
      $(".field--name-headquarters select.country.form-select").once("register-dups-trigger").each(function () {
        $(this).change(function () {
          $(this.form).find("input[type=\"submit\"][name=\"find_dups\"]").mousedown();
        })
      });
    }
  };

})(jQuery, Drupal);
