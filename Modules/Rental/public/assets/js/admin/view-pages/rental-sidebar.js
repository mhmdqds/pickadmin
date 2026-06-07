
"use strict";

$(window).on('load' , function() {
    if($(".navbar-vertical-content li.active").length) {
        $('.navbar-vertical-content').animate({
            scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
        }, 10);
    }
});

var $rows = $('#navbar-vertical-content li');
$('#search-sidebar-menu').keyup(function() {
    var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

    $rows.show().filter(function() {
        var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
        return !~text.indexOf(val);
    }).hide();
});
$(document).ready(function() {
        const $searchInput = $('#search');
        const $suggestionsList = $('#search-suggestions');
        const $rows = $('#navbar-vertical-content li');
        const $subrows = $('#navbar-vertical-content li ul li');

        const focusInput = () => updateSuggestions($searchInput.val());
        const hideSuggestions = () => $suggestionsList.slideUp(700);
        const showSuggestions = () => $suggestionsList.slideDown(700);
        let clickSuggestion = function() {
            let suggestionText = $(this).text();
            $searchInput.val(suggestionText);
            hideSuggestions();
            filterItems(suggestionText.toLowerCase());
            updateSuggestions(suggestionText);
        };
        let filterItems = (val) => {
            let unmatchedItems = $rows.show().filter((index, element) => !~$(element).text().replace(
                /\s+/g, ' ').toLowerCase().indexOf(val));
            let matchedItems = $rows.show().filter((index, element) => ~$(element).text().replace(/\s+/g,
                ' ').toLowerCase().indexOf(val));
            unmatchedItems.hide();
            matchedItems.each(function() {
                let $submenu = $(this).find($subrows);
                let keywordCountInRows = 0;
                $rows.each(function() {
                    let rowText = $(this).text().toLowerCase();
                    let valLower = val.toLowerCase();
                    let keywordCountRow = rowText.split(valLower).length - 1;
                    keywordCountInRows += keywordCountRow;
                });
                if ($submenu.length > 0) {
                    $subrows.show();
                    $submenu.each(function() {
                        let $submenu2 = !~$(this).text().replace(/\s+/g, ' ')
                            .toLowerCase().indexOf(val);
                        if ($submenu2 && keywordCountInRows <= 2) {
                            $(this).hide();
                        }
                    });
                }
            });
        };
        let updateSuggestions = (val) => {
            $suggestionsList.empty();
            suggestions.forEach(suggestion => {
                if (suggestion.toLowerCase().includes(val.toLowerCase())) {
                    $suggestionsList.append(
                        `<span class="search-suggestion badge badge-soft-light m-1 fs-14">${suggestion}</span>`
                    );
                }
            });
            // showSuggestions();
        };
        $searchInput.focus(focusInput);
        $searchInput.on('input', function() {
            updateSuggestions($(this).val());
        });
        $suggestionsList.on('click', '.search-suggestion', clickSuggestion);
        $searchInput.keyup(function() {
            filterItems($(this).val().toLowerCase());
        });
        $searchInput.on('focusout', hideSuggestions);
        $searchInput.on('focus', showSuggestions);
    });
