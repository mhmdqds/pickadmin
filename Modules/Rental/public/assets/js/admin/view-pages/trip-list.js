"use strict";
$(document).ready(function () {
    let defaultFilterCount = $('#get-default-filter-count').val();
    if(defaultFilterCount > 0)
    {
        $('#filter_count').html(defaultFilterCount);
    }

    $('#zone_ids').on('change', function () {
        $('#provider_ids').val(null).trigger('change');
        $('#provider_ids').trigger('change');
    });

    $('#provider_ids').select2({
            ajax: {
                url: $('#provider_ids').data('get-provider-url'),
            data: function (params) {
                return {
                    q: params.term,
                    zone_ids: $('#zone_ids').val(),
                    page: params.page
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            }
        }
    });

    $('#reset').on('click', function(){
        location.href = $(this).data('url');
    });

});
