function buildCaroussel(){
    var allCarousselContainers = document.getElementsByClassName('caroussel_for_wikidata_images'); 
    var baseURI = "https://commons.wikimedia.org/wiki/File:$1";
    for (var n = 0; n < allCarousselContainers.length; n++){
        var thisCaroussel = allCarousselContainers[n]; 
        var thisImages = thisCaroussel.dataset.content; 
        console.log(thisImages);
    }
}