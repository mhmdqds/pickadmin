"use strict";
$('#pac-input1').on('keydown', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
    }
});
$(document).on('ready', function() {
    $('.offcanvas').on('click', function() {
        $('.offcanvas, .floating--date').removeClass('active')
    })
    $('.floating-date-toggler').on('click', function() {
        $('.offcanvas, .floating--date').toggleClass('active')
    })

    let admin_zone_id = $('#data-set').data('admin-zone-id');
    if(admin_zone_id){
        $('#choice_zones').trigger('change');
    }
});

function readURL(input, viewer) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            $('#' + viewer).attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}


let myLatlng = {
    lat: $('#data-set').data('lat'),
    lng: $('#data-set').data('lng')
};
let map = new google.maps.Map(document.getElementById("map"), {
    zoom: 13,
    center: myLatlng,
});
let zonePolygon = null;
let infoWindow = new google.maps.InfoWindow({
    content: "Click the map to get Lat/Lng!",
    position: myLatlng,
});

let bounds = new google.maps.LatLngBounds();
function initMap() {
    const input = document.getElementById("pac-input1");
    const searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
    let markers = [];
    searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();
        if (places.length == 0) {
            return;
        }
        markers.forEach((marker) => {
            marker.setMap(null);
        });
        markers = [];
        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
            document.getElementById('latitude').value = place.geometry.location.lat();
            document.getElementById('longitude').value = place.geometry.location.lng();
            if (!place.geometry || !place.geometry.location) {
                console.log("Returned place contains no geometry");
                return;
            }
            const icon = {
                url: place.icon,
                size: new google.maps.Size(71, 71),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17, 34),
                scaledSize: new google.maps.Size(25, 25),
            };
            markers.push(
                new google.maps.Marker({
                    map,
                    icon,
                    title: place.name,
                    position: place.geometry.location,
                })
            );

            if (place.geometry.viewport) {
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
    });
}
initMap();

$('#choice_zones').on('change', function() {
    let id = $(this).val();
    let url = $('#choice_zones').data('zone-coordinates-url').replace('PLACEHOLDER_ID', id);

    $.get({
        url:url,
        dataType: 'json',
        success: function(data) {
            if (zonePolygon) {
                zonePolygon.setMap(null);
            }
            zonePolygon = new google.maps.Polygon({
                paths: data.coordinates,
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: 'white',
                fillOpacity: 0,
            });
            zonePolygon.setMap(map);

            let bounds = new google.maps.LatLngBounds();
            zonePolygon.getPaths().forEach(function(path) {
                path.forEach(function(latlng) {
                    bounds.extend(latlng);
                });
            });

            map.fitBounds(bounds);

            map.addListener('idle', function() {
                const customZoom = 15;
                if (map.getZoom() > customZoom) {
                    map.setZoom(customZoom);
                }
            });

            google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                infoWindow.close();
                infoWindow = new google.maps.InfoWindow({
                    position: mapsMouseEvent.latLng,
                    content: JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2),
                });

                let coordinates = JSON.parse(
                    JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2)
                );

                document.getElementById('latitude').value = coordinates['lat'];
                document.getElementById('longitude').value = coordinates['lng'];
                infoWindow.open(map);
            });
        },
    });
});

$("#vendor_form").on('keydown', function(e) {
    if (e.keyCode === 13) {
        e.preventDefault();
    }
})
$('#reset_btn').click(function() {
    $('#logoImageViewer').attr('src', $('#data-set').data('logoImageViewer'));
    $('#customFileEg1').val(null);
    $('#coverImageViewer').attr('src', $('#data-set').data('logoImageViewer'));
    $('#coverImageUpload').val(null);
    $('#choice_zones').val(null).trigger('change');
    $(".multiple-select2").val(null).trigger("change");
    $('#module_id').val(null).trigger('change');
    zonePolygon.setMap(null);
    $('#coordinates').val(null);
    $('#latitude').val(null);
    $('#longitude').val(null);
})
let zone_id = 0;
$('#choice_zones').on('change', function() {
    if ($(this).val()) {
        zone_id = $(this).val();
    }
});

$('#module_id').select2({
    ajax: {
        url: $('#data-set').data('get-all-modules-url'),
        data: function(params) {
            return {
                q: params.term,
                page: params.page,
                zone_id: zone_id
            };
        },
        processResults: function(data) {
            return {
                results: data
            };
        },
        __port: function(params, success, failure) {
            let $request = $.ajax(params);

            $request.then(success);
            $request.fail(failure);

            return $request;
        }
    }
});
$('.delivery-time').on('click', function() {
    let min = $("#minimum_delivery_time").val();
    let max = $("#maximum_delivery_time").val();
    let type = $("#delivery_time_type").val();
    $("#floating--date").removeClass('active');
    $("#time_view").val(min + ' to ' + max + ' ' + type);

})
        // ---- file upload with textbox
        $(document).ready(function() {
            function handleImageUpload(inputSelector, imgViewerSelector, textBoxSelector, iconSelector) {
                const inputElement = $(inputSelector);

                // Handle input change for file selection
                inputElement.on('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $(imgViewerSelector).attr('src', e.target.result).show();
                            $(textBoxSelector).hide();
                            $(iconSelector).remove();
                        };
                        reader.readAsDataURL(file);
                    }
                });

                // Handle drag-and-drop functionality
                const dropZone = inputElement.closest('.image--border');

                dropZone.on('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });

                dropZone.on('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });

                dropZone.on('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const file = e.originalEvent.dataTransfer.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $(imgViewerSelector).attr('src', e.target.result).show();
                            $(textBoxSelector).hide();
                            $(iconSelector).remove();
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Apply functionality to each upload element
            handleImageUpload(
                '#coverImageUpload',
                '#coverImageViewer',
                '#coverImageViewer ~ .upload-file__textbox',
                '#coverEditIcon'
            );

            handleImageUpload(
                '#customFileEg1',
                '#logoImageViewer',
                '#logoImageViewer ~ .upload-file__textbox',
                '#logoEditIcon'
            );
        });
        // ---- file upload with textbox ends
        $(document).on('ready', function() {
            $('.plan-slider').owlCarousel({
                loop: false,
                margin: 30,
                responsiveClass: true,
                nav: false,
                dots: false,
                items: 3,
                center: true,
                startPosition: 1,

                responsive: {
                    0: {
                        items: 1.1,
                        margin: 10,
                    },
                    375: {
                        items: 1.3,
                        margin: 30,
                    },
                    576: {
                        items: 1.7,
                    },
                    768: {
                        items: 2.2,
                        margin: 40,
                    },
                    992: {
                        items: 3,
                        margin: 40,
                    },
                    1200: {
                        items: 4,
                        margin: 40,
                    }
                }
            })

            $(document).on('keyup', 'input[name="password"]', function() {
                const password = $(this).val();
                const feedback = $('#password-feedback');

                const minLength = password.length >= 8;
                const hasLowerCase = /[a-z]/.test(password);
                const hasUpperCase = /[A-Z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);

                if (minLength && hasLowerCase && hasUpperCase && hasNumber && hasSymbol) {
                    feedback.text($('#data-set').data('password-valid'));
                    feedback.removeClass('invalid').addClass('valid');
                    feedback.removeClass('password-feedback');

                } else {
                    feedback.text($('#data-set').data('password-invalid'));
                    feedback.removeClass('valid').addClass('invalid');
                    feedback.removeClass('password-feedback');

                }
            });

            $(document).on('keyup', 'input[name="confirmPassword"]', function() {
                const password = $('input[name="password"]').val();
                const confirmPassword = $(this).val();
                const feedback = $('#invalid-feedback');

                if (confirmPassword == password && confirmPassword.length > 0) {
                    feedback.text($('#data-set').data('password-matched'));
                    feedback.removeClass('invalid').addClass('valid');
                    feedback.removeClass('invalid-feedback');

                } else {
                    feedback.text($('#data-set').data('password-not-matched'));
                    feedback.removeClass('valid').addClass('invalid');
                    feedback.removeClass('invalid-feedback');

                }
            });






            $('#nextStep').on('click', function (e) {
                const dataSet = document.getElementById("data-set");

                const messages = {
                    storeLogoRequired: dataSet.getAttribute("data-store-logo-required"),
                    storeNameRequired: dataSet.getAttribute("data-store-name-required"),
                    storeAddressRequired: dataSet.getAttribute("data-store-address-required"),
                    selectZone: dataSet.getAttribute("data-select-zone"),
                    mapLatLongRequired: dataSet.getAttribute("data-map-latlong-required"),
                    taxRequired: dataSet.getAttribute("data-tax-required"),
                    pickupZoneRequired: dataSet.getAttribute("data-pickup-zone-required"),
                    minDeliveryTimeRequired: dataSet.getAttribute("data-min-delivery-time-required"),
                    maxDeliveryTimeRequired: dataSet.getAttribute("data-max-delivery-time-required"),
                    firstNameRequired: dataSet.getAttribute("data-first-name-required"),
                    lastNameRequired: dataSet.getAttribute("data-last-name-required"),
                    phoneRequired: dataSet.getAttribute("data-phone-required"),
                    emailRequired: dataSet.getAttribute("data-email-required"),
                    passwordRequired: dataSet.getAttribute("data-password-required"),
                    confirmPasswordMismatch: dataSet.getAttribute("data-confirm-password-mismatch"),
                };

                const fileInputs = document.querySelectorAll('input[type="file"]');
                fileInputs.forEach(input => {
                    if (input.files.length === 0) {
                        toastr.error(messages.storeLogoRequired);
                        e.preventDefault();
                    } else if ($('#default_name').val().length === 0) {
                        toastr.error(messages.storeNameRequired);
                        e.preventDefault();
                    } else if ($('#default_address').val().length === 0) {
                        toastr.error(messages.storeAddressRequired);
                        e.preventDefault();
                    } else if (!$('#choice_zones').val()) {
                        toastr.error(messages.selectZone);
                        e.preventDefault();
                    } else if ($('#latitude').val().length === 0) {
                        toastr.error(messages.mapLatLongRequired);
                        e.preventDefault();
                    } else if ($('#longitude').val().length === 0) {
                        toastr.error(messages.mapLatLongRequired);
                        e.preventDefault();
                    } else if ($('#tax').val().length === 0) {
                        toastr.error(messages.taxRequired);
                        e.preventDefault();
                    } else if ($('#pickup_zones').val().length === 0) {
                        toastr.error(messages.pickupZoneRequired);
                        e.preventDefault();
                    } else if ($('#min').val().length === 0) {
                        toastr.error(messages.minDeliveryTimeRequired);
                        e.preventDefault();
                    } else if ($('#max').val().length === 0) {
                        toastr.error(messages.maxDeliveryTimeRequired);
                        e.preventDefault();
                    } else if ($('#f_name').val().length === 0) {
                        toastr.error(messages.firstNameRequired);
                        e.preventDefault();
                    } else if ($('#l_name').val().length === 0) {
                        toastr.error(messages.lastNameRequired);
                        e.preventDefault();
                    } else if ($('#phone').val().length < 5) {
                        toastr.error(messages.phoneRequired);
                        e.preventDefault();
                    } else if ($('#email').val().length === 0) {
                        toastr.error(messages.emailRequired);
                        e.preventDefault();
                    } else if ($('#signupSrPassword').val().length === 0) {
                        toastr.error(messages.passwordRequired);
                        e.preventDefault();
                    } else if ($('#signupSrConfirmPassword').val() !== $('#signupSrPassword').val()) {
                        toastr.error(messages.confirmPasswordMismatch);
                        e.preventDefault();
                    } else {
                        $('#businessSetup').removeClass('d-block').addClass('d-none');
                        $('#businessPlan').removeClass('d-none').addClass('d-block');
                        $(window).scrollTop(0);
                    }
                });
            });



            $('#backBusinessSetup').on('click', function () {
                $('#businessSetup').removeClass('d-none').addClass('d-block');
                $('#businessPlan').removeClass('d-block').addClass('d-none');
            });

        });


        $(window).on('load', function() {
            $('input[name="business_plan"]').each(function() {
                if ($(this).is(':checked')) {
                    if ($(this).val() == 'subscription-base') {
                        $('#subscription-plan').show()
                    } else {
                        $('#subscription-plan').hide()
                    }
                }
            })
            $('input[name="package_id"]').each(function() {
                if ($(this).is(':checked')) {
                    $(this).closest('.__plan-item').addClass('active')
                }
            })
        })
        $('input[name="business_plan"]').on('change', function() {
            if ($(this).val() == 'subscription-base') {
                $('#subscription-plan').slideDown()
            } else {
                $('#subscription-plan').slideUp()
            }
        })
        $('input[name="package_id"]').on('change', function() {
            $('input[name="package_id"]').each(function() {
                $(this).closest('.__plan-item').removeClass('active')
            })
            $(this).closest('.__plan-item').addClass('active')
        })
        $('#reset-btn').on('click', function() {
            location.reload()
        })
   $.fn.select2DynamicDisplay = function () {
            const limit = 100;
            function updateDisplay($element) {
                var $rendered = $element
                    .siblings(".select2-container")
                    .find(".select2-selection--multiple")
                    .find(".select2-selection__rendered");
                var $container = $rendered.parent();
                var containerWidth = $container.width();
                var totalWidth = 0;
                var itemsToShow = [];
                var remainingCount = 0;

                 var selectedItems = $element.select2("data");

                 var $tempContainer = $("<div>")
                    .css({
                        display: "inline-block",
                        padding: "0 15px",
                        "white-space": "nowrap",
                        visibility: "hidden",
                    })
                    .appendTo($container);

                 selectedItems.forEach(function (item) {
                    var $tempItem = $("<span>")
                        .text(item.text)
                        .css({
                            display: "inline-block",
                            padding: "0 12px",
                            "white-space": "nowrap",
                        })
                        .appendTo($tempContainer);

                    var itemWidth = $tempItem.outerWidth(true);

                    if (totalWidth + itemWidth <= containerWidth - 40) {
                        totalWidth += itemWidth;
                        itemsToShow.push(item);
                    } else {
                        remainingCount = selectedItems.length - itemsToShow.length;
                        return false;
                    }
                });

                $tempContainer.remove();

                const $searchForm = $rendered.find(".select2-search");

                var html = "";
                itemsToShow.forEach(function (item) {
                    html += `<li class="name">
                                        <span>${item.text}</span>
                                        <span class="close-icon" data-id="${item.id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
                                            </svg>
                                        </span>
                                        </li>`;
                });
                if (remainingCount > 0) {
                    html += `<li class="ms-auto">
                                        <div class="more">+${remainingCount}</div>
                                        </li>`;
                }

                if (selectedItems.length < limit) {
                    html += $searchForm.prop("outerHTML");
                }

                $rendered.html(html);

                function debounce(func, wait) {
                    let timeout;
                    return function (...args) {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), wait);
                    };
                }

                $(".select2-search input").on(
                    "input",
                    debounce(function () {
                        const inputValue = $(this).val().toLowerCase();
                        const $listItems = $(".select2-results__options li");
                        let matches = 0;

                        $listItems.each(function () {
                            const itemText = $(this).text().toLowerCase();
                            const isMatch = itemText.includes(inputValue);
                            $(this).toggle(isMatch);
                            if (isMatch) matches++;
                        });

                        if (matches === 0) {
                            $(".select2-results__options").append(
                                '<li class="no-results">No results found</li>'
                            );
                        } else {
                            $(".no-results").remove();
                        }
                    }, 100)
                );

                $(".select2-search input").on("keydown", function (e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        const inputValue = $(this).val().toLowerCase();
                        const $listItems = $(".select2-results__options li:not(.no-results)");
                        const matchedItem = $listItems.filter(function () {
                            return $(this).text().toLowerCase() === inputValue;
                        });

                        if (matchedItem.length > 0) {
                            matchedItem.trigger("mouseup");
                        }

                        $(this).val("");
                    }
                });
            }
            return this.each(function () {
                var $this = $(this);

                $this.select2({
                    tags: true,
                    maximumSelectionLength: limit,
                    placeholder: $('#data-set').data('select_pickup_zone'),
                });

                 $this.on("change", function () {
                    updateDisplay($this);
                });

                 updateDisplay($this);

                $(window).on("resize", function () {
                    updateDisplay($this);
                });
                $(window).on("load", function () {
                    updateDisplay($this);
                });

                 $(document).on(
                    "click",
                    ".select2-selection__rendered .close-icon",
                    function (e) {
                        e.stopPropagation();
                        var $removeIcon = $(this);
                        var itemId = $removeIcon.data("id");
                        var $this2 = $removeIcon
                            .closest(".select2")
                            .siblings(".multiple-select2");
                        $this2.val(
                            $this2.val().filter(function (id) {
                                return id != itemId;
                            })
                        );
                        $this2.trigger("change");
                    }
                );
            });
        };
        $(".multiple-select2").select2DynamicDisplay();

