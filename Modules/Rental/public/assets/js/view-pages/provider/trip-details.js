"use strict";
$('.js-select2-custom').each(function() {
    var select2 = $.HSCore.components.HSSelect2.init($(this));
});
$(document).ready(function () {
    $('.assign-vehicle-btn').on('click', function () {
        const detailsId = $(this).data('details_id');
        const vehicleId = $(this).data('vehicle_id');
        const tripId = $(this).data('trip_id');
        const quantity = $(this).data('quantity');
        const imgSrc = $(this).data('img');
        const name = $(this).data('name');
        const vendor = $(this).data('vendor');
        const category = $(this).data('category');
        const brand = $(this).data('brand');
        const list = $(this).data('list');
        const tripVehicleDetails = $(this).data('trip_vehicle_details');

        $('#vehicleImage').attr('src', imgSrc);
        $('#vehicleName').text(name);
        $('#vehicleQuantity').text(quantity);
        $('#vehicleVendor').text(vendor);
        $('#vehicleCategory').text(category);
        $('#vehicleBrand').text(brand);

        const tableBody = $('#assignVehicleModal tbody');
        tableBody.empty();

        let preCheckedIds = [];
        try {
            if (typeof tripVehicleDetails === 'string') {
                preCheckedIds = JSON.parse(tripVehicleDetails).map(item => item.vehicle_identity_id);
            } else {
                preCheckedIds = tripVehicleDetails.map(item => item.vehicle_identity_id);
            }
        } catch (error) {
            console.error('Error parsing trip_vehicle_details:', error);
        }

        if (list && Array.isArray(list)) {
            list.forEach((item, index) => {
                const isChecked = preCheckedIds.includes(item.id) ? 'checked' : '';
                const row = `
        <tr>
            <td>${index + 1}</td>
            <td>${item.vin_number || 'N/A'}</td>
            <td>${item.license_plate_number || 'N/A'}</td>
            <td>
                <div class="d-flex justify-content-center align-items-center">
                    <input class="form-check-input single-select m-auto position-relative" type="checkbox" name="vehicle_identity_ids[]" value="${item.id || ''}" ${isChecked}>
                    <input type="hidden" name="trip_id" value="${tripId}">
                    <input type="hidden" name="vehicle_id" value="${vehicleId}">
                    <input type="hidden" name="details_id" value="${detailsId}">
                </div>
            </td>
        </tr>
        `;
                tableBody.append(row);
            });
        } else {
            tableBody.append('<tr><td colspan="4" class="text-center">No data available</td></tr>');
        }

        let checkedCount = $('.single-select:checked').length;

        $('.single-select').on('change', function () {
            if ($(this).is(':checked')) {
                checkedCount++;
            } else {
                checkedCount--;
            }

            if (checkedCount > quantity) {
                $(this).prop('checked', false);
                checkedCount--;
                toastr.warning(`You can select up to ${quantity} vehicles only.`, '', {
                    closeButton: true,
                    progressBar: true
                });
            }
        });
    });

    let selectedDrivers = {};

    function initializeSelectedDrivers() {
        $('.driver-select').each(function() {
            let vehicleId = $(this).attr('name').match(/\[(.*?)\]/)[1];
            let selectedDriverId = $(this).val();

            if (selectedDriverId) {
                selectedDrivers[vehicleId] = selectedDriverId;
            }
        });

        disableUsedDrivers();
        updateUnassignedVehicleCount();
    }

    function disableUsedDrivers() {
        $('.driver-select option').prop('disabled', false).css('color', '');

        $('.driver-select').each(function() {
            let vehicleId = $(this).attr('name').match(/\[(.*?)\]/)[1];
            let selectedDriverId = selectedDrivers[vehicleId];

            if (selectedDriverId) {
                $('.driver-select').not(this).each(function() {
                    $(this).find(`option[value="${selectedDriverId}"]`).prop('disabled', true).css('color', 'gray');
                });
            }
        });
    }

    function updateUnassignedVehicleCount() {
        let unassignedCount = 0;

        $('.driver-select').each(function() {
            if (!$(this).val()) {
                unassignedCount++;
            }
        });

        $('#vehicle-assign-count').text(unassignedCount);
    }

    $('.assign-driver-modal').on('click', function() {
        $('#assignDriverModal').modal('show');
    });

    $('.driver-select').on('change', function() {
        let selectedDriverId = $(this).val();
        let vehicleId = $(this).attr('name').match(/\[(.*?)\]/)[1];

        if (selectedDriverId) {
            selectedDrivers[vehicleId] = selectedDriverId;
        }

        updateUnassignedVehicleCount();
        disableUsedDrivers();
    });

    initializeSelectedDrivers();

});
$(document).ready(function () {


    function providerLocationMap() {
        const grayStyle = [
            {
                featureType: "all",
                stylers: [{ saturation: -100 }, { lightness: 20 }]
            },
            {
                featureType: "road",
                stylers: [{ visibility: "on" }, { lightness: 30 }]
            },
            {
                featureType: "landscape",
                stylers: [{ lightness: 10 }, { saturation: -80 }]
            }
        ];

        const map = new google.maps.Map(document.getElementById("provider_map_canvas"), {
            center: {
                lat: $('#provider_latitude').data('provider-latitude'),
                lng: $('#provider_longitude').data('provider-longitude')
            },
            zoom: 14,
            styles: grayStyle
        });

        const providerLocation = {
            lat: $('#provider_latitude').data('provider-latitude'),
            lng: $('#provider_longitude').data('provider-longitude')
        };

        const providerMarker = new google.maps.Marker({
            position: providerLocation,
            map: map,
            title: $('#provider_name').data('provider-name'),
            icon: $('#map-marker-image').data('map-marker-image')
        });

        const infowindow = new google.maps.InfoWindow({
            content: `
                <div class="provider_div_float">
                    <img class="provider_logo" src="${ $('#provider_logo').data('provider-logo') }">
                </div>
                <div class="provider_name_div">
                    <b>${ $('#provider_name').data('provider-name') }</b><br />
                    ${ $('#provider_address').data('provider-address') }
                </div>
            `
        });

        google.maps.event.addListener(providerMarker, "click", function() {
            infowindow.open(map, providerMarker);
        });

        google.maps.event.addListenerOnce(map, "idle", function() {
            infowindow.open(map, providerMarker);
        });
    }

    function initializeCustomRouteLocationMap() {

        const grayStyle = [{
            featureType: "all",
            stylers: [{
                saturation: -100
            },
                {
                    lightness: 20
                },
            ]
        },
            {
                featureType: "road",
                stylers: [{
                    visibility: "on"
                },
                    {
                        lightness: 30
                    }
                ]
            },
            {
                featureType: "landscape",
                stylers: [{
                    lightness: 10
                },
                    {
                        saturation: -80
                    }
                ]
            }
        ];

        const map = new google.maps.Map(document.getElementById("custom_route_line_map_canvas"), {
            center: {
                lat: 23.766660,
                lng: 90.424993
            },
            zoom: 14,
            styles: grayStyle,
        });

        const infowindow = new google.maps.InfoWindow();
        const pickupLocation = {
            lat: $('#pickup-location-lat').data('pickup-location-lat'),
            lng: $('#pickup-location-lng').data('pickup-location-lng')
        };
        const destinationLocation = {
            lat: $('#destination-location-lat').data('destination-location-lat'),
            lng: $('#destination-location-lng').data('destination-location-lng')
        };

        function getDynamicMarkerSvg(dynamicColor) {
            return `
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30">
                                <g>
                                    <g clip-path="url(#clip0_5241_7679)">
                                        <path d="M14.7577 1C9.36898 1 5 5.3684 5 10.7577C5 14.0607 8.6658 19.7298 11.5035 23.6238C13.2959 26.0826 14.7577 27.8335 14.7577 27.8335C14.7621 27.8273 24.5154 16.1557 24.5154 10.7576C24.5154 5.3684 20.147 1 14.7577 1Z" fill="${dynamicColor}"/>
                                        <path d="M14.7575 3.43945C10.8843 3.43945 7.74414 6.57961 7.74414 10.4528C7.74414 12.4299 8.56258 14.2162 9.87865 15.4908H19.6363C20.953 14.2162 21.7708 12.4299 21.7708 10.4528C21.7708 6.57955 18.6313 3.43945 14.7575 3.43945Z" fill="white"/>
                                        <path d="M19.6366 15.0263V15.4904C16.917 18.1246 12.5984 18.1246 9.87891 15.4904V15.0263C9.87891 13.0052 11.517 11.3672 13.538 11.3672H15.9775C17.9985 11.3672 19.6366 13.0052 19.6366 15.0263Z" fill="white"/>
                                        <path d="M14.7578 11.3671C16.1051 11.3671 17.1972 10.275 17.1972 8.92772C17.1972 7.58045 16.1051 6.48828 14.7578 6.48828C13.4105 6.48828 12.3184 7.58045 12.3184 8.92772C12.3184 10.275 13.4105 11.3671 14.7578 11.3671Z" fill="white"/>
                                        <g clip-path="url(#clip1_5241_7679)">
                                            <path d="M15.0563 14.9415C14.999 14.9415 14.941 14.9362 14.8826 14.9262C14.4166 14.8445 14.0913 14.4569 14.0913 13.9839V11.6079H11.715C11.242 11.6079 10.8546 11.2822 10.773 10.8165C10.6916 10.3515 10.944 9.91486 11.3866 9.75352L18.7673 6.93652L15.9446 14.3155C15.8056 14.6992 15.4533 14.9415 15.056 14.9415H15.0563Z" fill="#1E2124" fill-opacity="0.6"/>
                                        </g>
                                    </g>
                                </g>
                                <defs>
                                    <clipPath id="clip0_5241_7679">
                                        <rect width="30" height="30" fill="white"/>
                                    </clipPath>
                                    <clipPath id="clip1_5241_7679">
                                        <rect width="8" height="8" fill="white" transform="translate(10.7578 6.94141)"/>
                                    </clipPath>
                                </defs>
                            </svg>
            `;
        }

        function createMarkerIconFromCssVariable(variableName) {
            const rootStyles = getComputedStyle(document.documentElement);
            const dynamicColor = rootStyles.getPropertyValue(variableName).trim();
            const svg = getDynamicMarkerSvg(dynamicColor);
            const base64Svg = `data:image/svg+xml;base64,${btoa(svg)}`;

            return {
                url: base64Svg,
                scaledSize: new google.maps.Size(30, 30),
            };
        }

        const destinationMarkerIcon = createMarkerIconFromCssVariable("--primary-clr");
        const pickupMarker = new google.maps.Marker({
            position: pickupLocation,
            map: map,
            title: "Pickup Location",
            icon: $('#map-marker-image').data('map-marker-image'),
        });

        google.maps.event.addListener(pickupMarker, "click", function() {
            infowindow.setContent('<div class="fs-12 font-medium">Pickup</div>');
            infowindow.open(map, pickupMarker);
        });

        const destinationMarker = new google.maps.Marker({
            position: destinationLocation,
            map: map,
            title: "Destination Location",
            icon: destinationMarkerIcon,
        });

        google.maps.event.addListener(destinationMarker, "click", function() {
            infowindow.setContent('<div class="fs-12 font-medium">Destination</div>');
            infowindow.open(map, destinationMarker);
        });

        addPolylineToMap(map, pickupLocation, destinationLocation);
    }
    $('#providerLocationModal').on('shown.bs.modal', function(event) {
        providerLocationMap();
    });

    function addPolylineToMap(map, pickupLocation, destinationLocation) {
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true,
            polylineOptions: {
                strokeColor: '#4D4D4D',
                strokeOpacity: 1.0,
                strokeWeight: 3
            },
        });

        const request = {
            origin: pickupLocation,
            destination: destinationLocation,
            travelMode: google.maps.TravelMode.DRIVING,
        };

        directionsService.route(request, function(response, status) {
            if (status === google.maps.DirectionsStatus.OK) {
                directionsRenderer.setDirections(response);
            } else {
                console.error("Directions request failed due to " + status);
            }
        });
    }


    initializeCustomRouteLocationMap();

    $('.select2-search__field').attr("placeholder", '<i class="tio-search"></i> Search Vendor');

});


$(document).ready(function () {

    let currentFieldType;
    let quantityUpdate;
    let upadet_data;
    let update_distance;
    let map, marker, searchBox;

    let pickupLocation = { lat: null, lng: null };
    let destinationLocation = { lat: null, lng: null };

    const pickupInputValue = $('#pickup-input').val();
    const destinationInputValue = $('#destination-input').val();

    if (pickupInputValue) {
        geocodeAddress(pickupInputValue, (location) => {
            pickupLocation = location;
        });
    }

    if (destinationInputValue) {
        geocodeAddress(destinationInputValue, (location) => {
            destinationLocation = location;
        });
    }

    $('#pickup-input').on('click', function () {
        currentFieldType = 'pickup';
        $('#mapModal').modal('show');
        initMap(pickupLocation);
    });

    $('#destination-input').on('click', function () {
        currentFieldType = 'destination';
        $('#mapModal').modal('show');
        initMap(destinationLocation);
    });
    function initMap(previousLocation) {
        const defaultLocation = { lat: 23.8103, lng: 90.4125 };

        const centerLocation = previousLocation.lat && previousLocation.lng
            ? previousLocation
            : defaultLocation;

        map = new google.maps.Map(document.getElementById('map'), {
            center: centerLocation,
            zoom: 13,
        });

        marker = new google.maps.Marker({
            position: previousLocation.lat && previousLocation.lng ? centerLocation : null,
            map: previousLocation.lat && previousLocation.lng ? map : null,
            draggable: true,
        });

        const input = document.getElementById('search-input');
        const autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.bindTo('bounds', map);

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();

            if (!place.geometry) {

                toastr.error('No details available for the selected location.');
                return;
            }

            map.setCenter(place.geometry.location);
            map.setZoom(15);
            marker.setPosition(place.geometry.location);

            const address = place.formatted_address;
            const latLng = place.geometry.location;

            updateFields(address, latLng.lat(), latLng.lng());
        });

        map.addListener('click', (event) => {
            const latLng = event.latLng;
            marker.setPosition(latLng);
            marker.setMap(map);

            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: latLng }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const address = results[0].formatted_address;
                    updateFields(address, latLng.lat(), latLng.lng());
                } else {
                    toastr.error('Failed to fetch address: ' + status);
                }
            });
        });
    }

    function updateFields(address, lat, lng) {
        if (currentFieldType === 'pickup') {
            $('#pickup-input').val(address);
            $('#pickup-lat').val(lat);
            $('#pickup-lng').val(lng);
            pickupLocation = { lat, lng };
        } else if (currentFieldType === 'destination') {
            $('#destination-input').val(address);
            $('#destination-lat').val(lat);
            $('#destination-lng').val(lng);
            destinationLocation = { lat, lng };
        }

        if (pickupLocation.lat && destinationLocation.lat) {
            calculateDistance();
        }

        $('#mapModal').modal('hide');
    }


    function calculateDistance() {
        const service = new google.maps.DistanceMatrixService();

        const request = {
            origins: [{ lat: pickupLocation.lat, lng: pickupLocation.lng }],
            destinations: [{ lat: destinationLocation.lat, lng: destinationLocation.lng }],
            travelMode: google.maps.TravelMode.DRIVING,
        };

        service.getDistanceMatrix(request, function (response, status) {
            if (status === google.maps.DistanceMatrixStatus.OK) {
                const distance = response.rows[0].elements[0].distance.text;
                $('#distance-input').val(distance);
                $('.distance-input').text(distance);

                updateCalculations(quantityUpdate = false,upadet_data= false,update_distance =1);
            } else {

                toastr.error('Error calculating distance: ' + status);

            }
        });
    }

    function geocodeAddress(address, callback) {
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: address }, (results, status) => {
            if (status === 'OK' && results[0]) {
                const location = results[0].geometry.location;
                callback({ lat: location.lat(), lng: location.lng() });
            } else {
                console.error('Geocode failed: ' + status);
            }
        });
    }

    function updateOverallTotal(response) {
        let overallTotal = 0;

        $('.fare-total').each(function () {
            let fare = parseFloat($(this).val().replace(/[^0-9.-]+/g, ""));
            if (!isNaN(fare)) {
                overallTotal += fare;
            }
        });

        let subtotal = response.subTotal;
        let grandTotal = response.grandTotal;
        let taxIncluded = $('#tax_included').val();

        $('.total_fare').text(formatCurrency(subtotal));
        $('.subtotal').text(formatCurrency(subtotal));
        $('.grand-total').text(formatCurrency(response.grandTotal));
        $('.coupon_discount_amount').text( '-'+ formatCurrency(response.couponDiscount));
        $('.discount_amount').text('-'+ formatCurrency(response.discount));
        $('.tax_amount').text((taxIncluded ? '' : '+') + formatCurrency(response.taxAmount));
        $('.ref_bonus_amount').text( '-'+ formatCurrency(response.refBonus));
        $('.additional_charge').text('+'+ formatCurrency(response.additionalCharge));
    }

    function formatCurrency(value) {
        return $("#currency_symbol").val() + value;
    }

    $('#edit-trip').on('click', function () {
        updateCalculations(quantityUpdate = false,upadet_data= 1);
        $('#edit-trip').attr("disabled", true);
    });

    let originalValues = {};
    $('.quantity-input, .fare-total').each(function() {
        const id = $(this).data('id');
        originalValues[id] = {
            quantity: $(this).data('max_original_quantity') || $(this).val(),
            price: $(this).data('old-value') || $(this).val()
        };
    });


    $(document).on('keydown', '.quantity-input, .fare-total', function(event) {
        if (event.key === '-' || event.keyCode === 189) {
            event.preventDefault();
        }
    });
    $('.quantity-input').on('input', function() {
        const $this = $(this);
        const maxQuantity = parseInt($this.data('max_quantity'));
        const tripDetailId = $this.data('id');
        const vehicleId = $this.data('vehicle_id');
        let quantity = parseInt($this.val());
        $this.closest('tr').find('.eta_amount_mt').addClass('d-none');
        $this.closest('tr').find('.eta_amount').removeClass('mt-3');

        if (quantity > maxQuantity) {
            quantity = maxQuantity;
            $this.val(maxQuantity);

            let maxQuantityMsg = $('#app-data').data('max-quantity-msg');
            toastr.warning(maxQuantityMsg + ' ' + maxQuantity);
        }

        updateCalculations(vehicleId,false);
    });

    $('.fare-total').on('input', function() {
        const $this = $(this);
        const tripDetailId = $this.data('id');
        const vehicleId = $this.data('vehicle_id');
        const originalPrice = parseFloat($this.data('old-value'));

        $this.closest('tr').find('.eta_amount').removeClass('d-none').addClass('mt-3');
        $this.closest('td').find('.eta_amount_mt').removeClass('d-none');
        updateCalculations(quantityUpdate = false,upadet_data= false);
    });

    $('#pickup-input, #destination-input').on('change', function() {
        updateCalculations(quantityUpdate = false,upadet_data= false);
    });



    function updateCalculations(quantityUpdate = false,upadet_data= false, update_distance=false) {
        const formData = new FormData($('#updateForm')[0]);

        formData.append('update', upadet_data);
        formData.append('update_distance', update_distance);

        $('.quantity-input').each(function() {
            formData.append('quantityUpdate', quantityUpdate);
            formData.append('quantities[]', $(this).val());
            formData.append('trip_detail_ids[]', $(this).data('id'));
            formData.append('vehicle_ids[]', $(this).data('vehicle_id'));
        });

        $('.fare-total').each(function() {
            formData.append('prices[]', $(this).val().replace(/[^0-9.]/g, ''));
        });

        $.ajax({
            url: $('#get_calculation_url').data('url'),
            type: 'post',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {

                if (response.status === 'success') {
                    updateOverallTotal(response);
                    if (response.details) {
                        response.details.forEach(detail => {
                            $(`[data-id="${detail.id}"].fare-total`).val(detail.calculated_price);
                            $(`#est_${detail.id}`).text(detail.originalPrice * detail.quantity);
                        });
                    }
                }
                else if(response.status === 'updated'){
                    toastr.success(response.message);
                    location.reload();
                }
                else {
                    toastr.error(response.message || 'Calculation failed');
                    $('#edit-trip').attr("disabled", false);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON.message || 'Failed to update calculations');

                $('#edit-trip').attr("disabled", false);
            }
        });
    }

    $('#reset_btn').on('click', function() {
        Object.keys(originalValues).forEach(id => {
            $(`.quantity-input[data-id="${id}"]`).val(originalValues[id].quantity);
            $(`.fare-total[data-id="${id}"]`).val(originalValues[id].price);
        });
        $('.eta_amount_mt').addClass('d-none');
    });

    $('.close-modal').on('click', function() {
        $('.eta_amount_mt').addClass('d-none');
        $('#editTripModal').modal('hide');
    });






});
