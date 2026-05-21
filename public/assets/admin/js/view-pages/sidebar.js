"use strict";
$(window).on('load', function () {
    if ($(".navbar-vertical-content li.active").length) {
        $('.navbar-vertical-content').animate({
            scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
        }, 300);
    }
});

var $navItems = $('#navbar-vertical-content > ul > li');
$('#search').keyup(function () {
    var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();
    $navItems.show().filter(function () {
        var $listItem = $(this);
        var text = $listItem.text().replace(/\s+/g, ' ').toLowerCase();
        var $list = $listItem.closest('li');

        return !~text.indexOf(val) && !$list.text().toLowerCase().includes(val);
    }).hide();
});
