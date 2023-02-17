//tileserver = https://operations.osmfoundation.org/policies/tiles/

function buildMaps(){
    var allMapContainers = document.getElementsByClassName('geocontainer_for_wikidata_coords'); 
    for (var n = 0; n < allMapContainers.length; n++){
        var thisMap = allMapContainers[n]; 
        var thisCoords = JSON.parse(thisMap.dataset.coordinates); 
        var thisInnerTarget = thisMap.dataset.wdprop; 
        var leafletMap = L.map(thisInnerTarget).setView([51.505, -0.09], 13);;
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(leafletMap);

        console.log(thisCoords);
        var coordLength = thisCoords.length; 
        var markers = [];
        for (var m = 0; m < coordLength; m++){
            //with  > 0 choose non-primary iconshape.
            coordBox = thisCoords[m]; 
            lat = coordBox[0];
            long = coordBox[1]; 
            /*TODO: */
            //add the marker(s) to a featuregroup
            //Get the featuregroup and fetch the bounds. 
            //add padding the bounds .pad(1)
            //make the map fit the padded bounds!
            markers.push(L.marker([lat, long]));//.addTo(leafletMap);
        }
        L.featureGroup(markers).addTo(leafletMap); 
        console.log(L); 
    }
}