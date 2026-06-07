"use strict";


document.addEventListener("DOMContentLoaded", function () {
    $(".js-data-example-ajax").each(function () {
        let $select = $(this);
        let url = $select.data("get-provider-url");
        let zoneId = $select.data("zone-id");
        let moduleId = $select.data("module-id");

        if (!url) {
            console.error("Select2: Missing data-url attribute.");
            return;
        }

        $select.select2({
            ajax: {
                url: url,
                data: function (params) {
                    let requestData = {
                        q: params.term, // Search term
                        page: params.page
                    };

                    if (zoneId) {
                        requestData.zone_ids = [zoneId];
                    }

                    if (moduleId) {
                        requestData.module_id = moduleId;
                    }

                    return requestData;
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                __port: function (params, success, failure) {
                    let $request = $.ajax(params);
                    $request.then(success);
                    $request.fail(failure);
                    return $request;
                }
            }
        });
    });
});
