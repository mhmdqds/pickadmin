'use strict';

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
                    .siblings(".basic-multiple-select2");
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
$(".basic-multiple-select2").select2DynamicDisplay();


$(document).on('ready', function() {
    $('#pac-input1').on('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
        }
    });

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


let myLatlng = { lat: $('#data-set').data('store-lat'), lng: $('#data-set').data('store-lng') };
const map = new google.maps.Map(document.getElementById("map"), {
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
    new google.maps.Marker({
        position: { lat: $('#data-set').data('store-lat'), lng: $('#data-set').data('store-lng') },
        map,
        title: $('#data-set').data('store-name'),
    });
    infoWindow.open(map);
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


$(document).on('ready', function () {
    function updateZone(id) {
        let url = $('#choice_zones').data('zone-coordinates-url').replace('PLACEHOLDER_ID', id)
        $.get({
            url: url,
            dataType: 'json',
            success: function (data) {
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

                google.maps.event.addListener(zonePolygon, 'click', function (mapsMouseEvent) {
                    infoWindow.close();
                    infoWindow = new google.maps.InfoWindow({
                        position: mapsMouseEvent.latLng,
                        content: JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2),
                    });

                    let coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    coordinates = JSON.parse(coordinates);

                    document.getElementById('latitude').value = coordinates['lat'];
                    document.getElementById('longitude').value = coordinates['lng'];
                    infoWindow.open(map);
                });
            },
        });
    }

    let id = $('#choice_zones').val();
    updateZone(id);

    $('#choice_zones').on('change', function () {
        let newId = $(this).val();
        updateZone(newId);
    });
});
$("#vendor_form").on('keydown', function(e) {
    if (e.keyCode === 13) {
        e.preventDefault();
    }
})

let initialSelectedZones = [];
$(".basic-multiple-select2 option:selected").each(function () {
    initialSelectedZones.push($(this).val());
});
$('#reset_btn').click(function () {
    const defaultLogo = $('#data-set').data('store-logo');
    const defaultCoverPhoto = $('#data-set').data('store-cover-photo');

    if ($('#customFileEg1').val()) {
        $('#logoImageViewer').attr('src', defaultLogo);
        $('#customFileEg1').val(null);
    } else {
        $('#logoImageViewer').attr('src', defaultLogo);
    }

    if ($('#coverImageUpload').val()) {
        $('#coverImageViewer').attr('src', defaultCoverPhoto);
        $('#coverImageUpload').val(null);
    } else {
        $('#coverImageViewer').attr('src', defaultCoverPhoto);
    }

    const zoneValue = $('#data-set').data('store-zone-id');
    if (zoneValue) {
        $('#choice_zones').val(zoneValue).trigger('change');
    }

    $('#module_id').val(null).trigger('change');
    zonePolygon.setMap(null);
    $('#coordinates').val(null);
    $('#latitude').val(null);
    $('#longitude').val(null);
    $(".basic-multiple-select2").val(initialSelectedZones).trigger("change");
});
let zone_id = 0;
$('#choice_zones').on('change', function() {
    if ($(this).val()) {
        zone_id = $(this).val();
    }
});
$('.delivery-time').on('click', function() {
    let min = $("#minimum_delivery_time").val();
    let max = $("#maximum_delivery_time").val();
    let type = $("#delivery_time_type").val();
    $("#floating--date").removeClass('active');
    $("#time_view").val(min + ' to ' + max + ' ' + type);

})

$(document).ready(function() {
    function handleImageUpload(inputSelector, imgViewerSelector, textBoxSelector) {
        const inputElement = $(inputSelector);

        inputElement.on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(imgViewerSelector).attr('src', e.target.result).show();
                    $(textBoxSelector).hide();
                };
                reader.readAsDataURL(file);
            }
        });

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
                };
                reader.readAsDataURL(file);
            }
        });
    }

    handleImageUpload(
        '#coverImageUpload',
        '#coverImageViewer',
        '#coverImageViewer ~ .upload-file__textbox'
    );

    handleImageUpload(
        '#customFileEg1',
        '#logoImageViewer',
        '#logoImageViewer ~ .upload-file__textbox'
    );
});
$(document).on('ready', function() {
   
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
            feedback.text($('#data-set').data('confirm-password-mismatch'));
            feedback.removeClass('valid').addClass('invalid');
            feedback.removeClass('invalid-feedback');
        }
    });

});
