//tileserver = https://operations.osmfoundation.org/policies/tiles/

function buildMaps(){
    var allMapContainers = document.getElementsByClassName('geocontainer_for_wikidata_coords');
    var customMarker = L.icon({
        iconUrl: '/CSS/leaflet/images/primary-icon.png',
        shadowUrl: '/CSS/leaflet/images/marker-shadow.png',
        iconSize:     [25, 41], // size of the icon
        iconAnchor:   [12, 41], // point of the icon which will correspond to marker's location
    });
    //console.log(allMapContainers.length);
    for (var n = 0; n < allMapContainers.length; n++){
        var thisMap = allMapContainers[n]; 
        var thisCoords = JSON.parse(thisMap.dataset.coordinates); 
        var thisInnerTarget = thisMap.dataset.wdprop; 
        var leafletMap = L.map(thisInnerTarget, {
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
            coordBox = thisCoords[m];
            lat = coordBox[0];
            long = coordBox[1];
            if(m === 0){
                markers.push(L.marker([lat, long], {icon: customMarker}));
            }else{
                markers.push(L.marker([lat, long]));
            }
        }
        var featuregroup = L.featureGroup(markers);
        featuregroup.addTo(leafletMap);
        leafletMap.setView(featuregroup.getBounds().getCenter(),12);
        leafletMap.setMaxBounds(leafletMap.getBounds().pad(1));
    }
}