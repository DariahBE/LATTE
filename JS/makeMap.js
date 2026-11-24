// OpenStreetMap tiles: https://operations.osmfoundation.org/policies/tiles/

function buildMaps(){
    if (typeof L === 'undefined') {
        return;
    }
    var allMapContainers = document.getElementsByClassName('geocontainer_for_wikidata_coords');
    var customMarker = L.icon({
        iconUrl: '/CSS/leaflet/images/primary-icon.png',
        shadowUrl: '/CSS/leaflet/images/marker-shadow.png',
        iconSize:     [25, 41],
        iconAnchor:   [12, 41],
    });

    for (var n = 0; n < allMapContainers.length; n++){
        var thisMap = allMapContainers[n]; 
        var targetDivForMap = thisMap.querySelector('div') || thisMap;

        // Skip if map is already initialized on this container
        if (targetDivForMap._leaflet_id || thisMap._leaflet_id) {
            continue;
        }

        var thisCoords = [];
        try {
            thisCoords = JSON.parse(thisMap.dataset.coordinates);
        } catch(e) {
            continue;
        }

        var leafletMap = L.map(targetDivForMap, {
            minZoom: 6,
            maxZoom: 15, 
            maxBoundsViscosity: 1.0    
        });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(leafletMap);

        var coordLength = thisCoords.length; 
        var markers = [];
        for (var m = 0; m < coordLength; m++){  
            var coordBox = thisCoords[m];
            var lat = coordBox[0];
            var long = coordBox[1];
            if(m === 0){
                markers.push(L.marker([lat, long], {icon: customMarker}));
            }else{
                markers.push(L.marker([lat, long]));
            }
        }
        if (markers.length > 0) {
            var featuregroup = L.featureGroup(markers);
            featuregroup.addTo(leafletMap);
            leafletMap.setView(featuregroup.getBounds().getCenter(), 12);
            leafletMap.setMaxBounds(leafletMap.getBounds().pad(1));
        }
    }
}
