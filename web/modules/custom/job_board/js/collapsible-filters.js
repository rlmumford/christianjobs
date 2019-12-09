(function ($, Drupal) {
  Drupal.behaviors.jobBoardCollapsibleFilters = {
    attach: function attach(context) {
      $(".collapsible-filter-block", context).once("collapsible-filter-block").each(function() {
        $(this).addClass('collapsible-filter-block-processed');

        let block = this;

        $(this).children("form, .layout").hide();
        $(this).children("h2").click(function(e) {
          if (!$(block).hasClass("collapsible-filter-block-expanded")) {
            $(block).addClass("collapsible-filter-block-expanded");
          }

          e.preventDefault();
          $(block).children("form, .layout").slideToggle("slow", function() {
            $(block).toggleClass("collapsible-filter-block-expanded", $(this).is(":visible"));
          })
        })
      });
    }
  };
})(jQuery, Drupal);
