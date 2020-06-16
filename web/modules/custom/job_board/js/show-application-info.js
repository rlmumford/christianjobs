(function ($, Drupal) {
  Drupal.behaviors.jobBoardShowApplicationInfo = {
    attach: function attach(context) {
      $("a.show-application-info-toggle", context).click(function(e) {
        e.preventDefault(); e.stopPropagation();

        let siblings = $(this).siblings(".application-info");
        siblings.css(
          "display",
          siblings.css("display") === "none" ? "block" : "none"
        );

        $(this).once("show-application-info-log").each(function() {
          let ajax = Drupal.ajax({
            element: this,
            event: "show-application-info"
          });
          ajax.execute();
        });
      })
    }
  };
})(jQuery, Drupal);
