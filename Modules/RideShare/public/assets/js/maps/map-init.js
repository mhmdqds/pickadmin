"use strict";

$(document).ready(function () {
    let bounds = new google.maps.LatLngBounds();

    function initMap(mapSelector, lat, lng, title, markersData, input, data = []) {
        let polygons = [];
        const map = new google.maps.Map(document.getElementById(mapSelector), {
            zoom: 13,
            center: {lat: lat, lng: lng},
            fullscreenControl: false,
            mapTypeControlOptions: {
                position: google.maps.ControlPosition.BOTTOM_LEFT,
            },
        });


        for (let i = 0; i < data.length; i++) {
            polygons.push(new google.maps.Polygon({
                paths: data[i],
                strokeColor: "#000000",
                strokeOpacity: 0.2,
                strokeWeight: 2,
                fillColor: "#000000",
                fillOpacity: 0.05,
            }));
            polygons[i].setMap(map);
            polygons[i].getPaths().forEach(function (path) {
                path.forEach(function (latlng) {
                    bounds.extend(latlng);
                });
            });
        }
        map.fitBounds(bounds);


        const infoWindow = new google.maps.InfoWindow();

        const mapMarkers = markersData.map(function (data) {
            const marker = new google.maps.Marker({
                position: data.position,
                title: data.title,
                icon: data.icon,
            });

            google.maps.event.addListener(marker, "click", function () {
                infoWindow.setContent(
                    `<div class="map-clusters-custom-window"><h6>${data.title}</h6></div>`
                );
                infoWindow.open(map, marker);
                google.maps.event.addListener(map, "click", function () {
                    infoWindow.close();
                });
            });

            return marker;
        });

        new markerClusterer.MarkerClusterer({
            map: map,
            markers: mapMarkers,
        });

        const searchBox = new google.maps.places.SearchBox(input);

        map.addListener("bounds_changed", function () {
            searchBox.setBounds(map.getBounds());
        });

        let searchMarkers = [];

        searchBox.addListener("places_changed", function () {
            const places = searchBox.getPlaces();

            if (places.length === 0) {
                return;
            }

            searchMarkers.forEach(function (marker) {
                marker.setMap(null);
            });
            searchMarkers = [];

            const bounds = new google.maps.LatLngBounds();

            places.forEach(function (place) {
                if (!place.geometry || !place.geometry.location) {
                    console.log("Returned place contains no geometry");
                    return;
                }

                const marker = new google.maps.Marker({
                    map: map,
                    icon: {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    },
                    title: place.name,
                    position: place.geometry.location,
                });

                searchMarkers.push(marker);

                if (place.geometry.viewport) {
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });

            map.fitBounds(bounds);
        });
    }

    $(".map-container").each(function () {
        const map = $(this).find(".map");
        const input = $(this).find(".map-search-input")[0];
        const lat = map.data("lat");
        const lng = map.data("lng");
        const title = map.data("title");
        const markers = map.data("markers");
        const polygonData = map.data("polygon");

        initMap(map.attr("id"), lat, lng, title, markers, input, polygonData);
    });
});
