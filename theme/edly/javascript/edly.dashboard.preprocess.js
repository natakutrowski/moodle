(function($) {
    $(document).ready(function() {
    // Custom Select Search w/ Icons
        $("div[id$='edly_icon_class'], div[id^='edly_icon_class'], .edly_icon_class").each(function() {
            $(this).find(".custom-select").each(function() {
                $(this).wrap("<div class='ui_kit_select_search'></div>");
                $(this).find("option").each(function() {
                    var $edlyIcon = $(this).attr("value");
                    $(this).attr("data-tokens", $edlyIcon).attr("data-icon", $edlyIcon).attr("data-subtext", $edlyIcon);
                });
                $(this).addClass("selectpicker").attr("data-live-search", "true").attr("data-width", "100%").removeClass("custom-select");
            });
        });
    });

  }(jQuery));
