"use strict";
let map;
let highlightedZone = null;
let polygons = {};
let markers = {};




function initializeMap() {
    const mapContainer = document.getElementById('mapContainer');
    if (!mapContainer) return;

    // Parse data from HTML attributes
    const mapConfig = {
        businessZoneCoords: [],
        businessCenter: { lat: 23.8103, lng: 90.4125 }, // Default center
        markerIconUrl: '',
        pickupZones: []
    };

    try {
        // Get business coordinates
        const businessCoords = JSON.parse(mapContainer.dataset.businessCoordinates || '[]');
        mapConfig.businessZoneCoords = businessCoords.map(coords => ({
            lat: coords[1],
            lng: coords[0]
        }));

        // Get business center
        mapConfig.businessCenter = JSON.parse(mapContainer.dataset.businessCenter || '{}');

        // Get marker icon
        mapConfig.markerIconUrl = mapContainer.dataset.markerIcon;

        // Get pickup zones
        const pickupZonesData = JSON.parse(mapContainer.dataset.pickupZones || '[]');
        mapConfig.pickupZones = pickupZonesData.map(zone => ({
            id: zone.id,
            coordinates: zone.coordinates.map(coords => ({
                lat: coords[1],
                lng: coords[0]
            }))
        }));

    } catch (error) {
        console.error('Error parsing map data:', error);
        return;
    }

    initMap(mapConfig);
}

function initMap(config) {
    // Initialize map with default center
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: config.businessCenter || { lat: 23.8103, lng: 90.4125 }
    });

    const bounds = new google.maps.LatLngBounds();

    // Create business zone polygon
    if (config.businessZoneCoords?.length) {
        const businessZonePolygon = new google.maps.Polygon({
            paths: config.businessZoneCoords,
            strokeColor: "#aaaaaa",
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: "rgba(181, 181, 181, 0.45)",
            fillOpacity: 0.35
        });

        polygons['business'] = businessZonePolygon;
        businessZonePolygon.setMap(map);
    }

    // Add business center marker
    if (config.businessCenter) {
        const marker = new google.maps.Marker({
            position: config.businessCenter,
            map: map,
            icon: {
                url: config.markerIconUrl,
                scaledSize: new google.maps.Size(30, 30)
            }
        });
    }

    // Create pickup zone polygons
    if (config.pickupZones?.length) {
        config.pickupZones.forEach(zone => {
            const pickupZonePolygon = new google.maps.Polygon({
                paths: zone.coordinates,
                strokeColor: "#aaaaaa",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "rgba(181, 191, 181, 0.45)",
                fillOpacity: 0.35
            });

            zone.coordinates.forEach(coord => {
                bounds.extend(new google.maps.LatLng(coord.lat, coord.lng));
            });

            polygons[`pickup_${zone.id}`] = pickupZonePolygon;
            pickupZonePolygon.setMap(map);
        });
    }

    // Fit map to bounds if there are any points
    if (!bounds.isEmpty()) {
        map.fitBounds(bounds);
    }
}

function highlightZone(type, id = null) {
    if (highlightedZone) {
        highlightedZone.setOptions({
            strokeColor: "#b4b2b273",
            fillColor: "rgba(172, 172, 172, 0.45)",
            fillOpacity: 0.35
        });
    }

    if (type === 'business') {
        highlightedZone = polygons['business'];
        highlightPolygon(highlightedZone);
    } else if (type === 'pickup') {
        highlightedZone = polygons['pickup_' + id];
        highlightPolygon(highlightedZone);
    }

    if (highlightedZone) {
        const bounds = new google.maps.LatLngBounds();
        highlightedZone.getPath().forEach(coord => bounds.extend(coord));
        map.fitBounds(bounds);
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeMap();

    // Business zone button listener
    const businessZoneBtn = document.querySelector('.business-zone-btn');
    if (businessZoneBtn) {
        businessZoneBtn.addEventListener('click', function() {
            highlightZone('business');
        });
    }

    // Pickup zone buttons listeners
    const pickupZoneBtns = document.querySelectorAll('.pickup-zone-btn');
    pickupZoneBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const zoneId = this.getAttribute('data-zone-id');
            highlightZone('pickup', zoneId);
        });
    });
});


function highlightPolygon(polygon) {
    polygon.setOptions({
        strokeColor: "#818181",
        fillColor: "rgba(172, 172, 172, 0.45)",
        fillOpacity: 0.5
    });
}

function getPolygonCenter(polygon) {
    const path = polygon.getPath();
    let latSum = 0, lngSum = 0;
    let numCoords = path.getLength();
    path.forEach(function (latLng) {
        latSum += latLng.lat();
        lngSum += latLng.lng();
    });
    return { lat: latSum / numCoords, lng: lngSum / numCoords };
}


$(document).ready(function () {
    $('.description-text').each(function () {
        const $descriptionText = $(this);
        const $shortDescription = $descriptionText.find('.short-description');
        const $fullDescription = $descriptionText.find('.full-description');
        const $seeMore = $descriptionText.find('.see-more');

        const fullDescriptionLength = $fullDescription.text().trim().length;

        if (fullDescriptionLength > 500) {
            $seeMore.show();
        } else {
            $seeMore.hide();
        }

        $seeMore.on('click', function (e) {
            console.log($shortDescription)
            console.log($fullDescription)
            e.preventDefault();

            $shortDescription.toggle();
            $fullDescription.toggle();

            if ($fullDescription.is(':visible')) {
                $(this).text('See less');
            } else {
                $(this).text('See more');
            }
        });
    });
});
function request_alert(url, message) {
    Swal.fire({
        title: $('#data-set').data('translate-are-you-sure'),
        text: message,
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#FC6A57',
        cancelButtonText: $('#data-set').data('translate-no'),
        confirmButtonText: $('#data-set').data('translate-yes'),
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            location.href = url;
        }
    })
}

$('#add_transaction').on('submit', function (e) {
    e.preventDefault();
    let formData = new FormData(this);
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.post({
        url: $('#data-set').data('store-transaction-url'),
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function (data) {
            if (data.errors) {
                for (let i = 0; i < data.errors.length; i++) {
                    toastr.error(data.errors[i].message, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            } else {
                toastr.success($('#data-set').data('translate-transaction-saved'), {
                    CloseButton: true,
                    ProgressBar: true
                });
                setTimeout(function () {
                location.reload();
            }, 2000);
            }
        }
    });
});
