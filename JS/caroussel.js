function buildCaroussel(){
    var allCarousselContainers = document.getElementsByClassName('caroussel_for_wikidata_images'); 
    //Limit to 1000PX width, makes request lighter; 
    //use the special pages to get the img source; no real requirement to replace whitespaces by '_'; but it is concidered a best practice. So do that anyway. 
    var baseURI = "https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/$1&width=1000"; 
    var attributionBaseURI = "https://commons.wikimedia.org/wiki/File:$1"; 
    for (var n = 0; n < allCarousselContainers.length; n++){
        var thisCaroussel = allCarousselContainers[n]; 
        var thisImages = JSON.parse(thisCaroussel.dataset.content); 
        //foreach carousell, load images: 
        var allImagesInList = []; 
        for (var j = 0; j < thisImages.length; j++){
            var imageURI = baseURI.replace('$1', thisImages[j].replace(' ', '_')); 
            var attributionURI = attributionBaseURI.replace('$1', thisImages[j].replace(' ', '_'));
            allImagesInList.push([imageURI, attributionURI]); 

        }
        //show the first image in the list: 
        var showIndex = 0; 
        function showImageAtIndex(i = 0){
            if (i < 0){showIndex = 0; i = 0; }
            if (i > allImagesInList.length-1){showIndex = allImagesInList.length-1; i = showIndex; }
            var outerCarousselDiv = document.createElement('div'); 
            outerCarousselDiv.classList.add('board');
            var innerCarousselDiv = document.createElement('div'); 
            innerCarousselDiv.classList.add('p-4', 'm-4');
            var prev_arrow = document.createElement('i');
            var next_arrow = document.createElement('i');
            prev_arrow.classList.add('gg-chevron-left', 'float-left');
            next_arrow.classList.add('gg-chevron-right', 'float-right'); 
            if(i == 0){
                prev_arrow.classList.add('invisible', 'hidden', 'disabled');
            }else{
                prev_arrow.classList.remove('invisible', 'hidden', 'disabled');
            }
            if(i == allImagesInList.length-1){
                next_arrow.classList.add('invisible', 'hidden', 'disabled');
            }else{
                next_arrow.classList.remove('invisible', 'hidden', 'disabled');
            }
            prev_arrow.addEventListener('click', function(){showIndex--; showImageAtIndex(showIndex);})
            next_arrow.addEventListener('click', function(){showIndex++; showImageAtIndex(showIndex);})
            var copyRightDiv = document.createElement("div"); 
            var goToWikidata = '<p class="text-xs"><span class="font-bold">Copyright Notice: </span><a href="'+allImagesInList[i][1]+'" target="_blank">Courtesy of Wikimedia Commons.</a>'; 
            copyRightDiv.innerHTML = goToWikidata; 
            var img = document.createElement('img'); 
            //BUG: when doing requests of an image: you get a cookie warning. 
            //document.cookie = "NetworkProbeLimit=0; SameSite=None";
            // https://gerrit.wikimedia.org/r/c/operations/puppet/+/989457
            // Cookie “NetworkProbeLimit” does not have a proper “SameSite” attribute value 
            img.setAttribute('src', allImagesInList[i][0]);
            innerCarousselDiv.appendChild(img); 
            outerCarousselDiv.appendChild(innerCarousselDiv); 
            outerCarousselDiv.appendChild(prev_arrow); 
            outerCarousselDiv.appendChild(next_arrow); 
            outerCarousselDiv.appendChild(copyRightDiv);
            var board = thisCaroussel.getElementsByClassName('board');
            for(var b=0; b<board.length; b++){
                board[b].parentNode.removeChild(board[b]);
            }
            thisCaroussel.appendChild(outerCarousselDiv)
        }
        showImageAtIndex(showIndex); 
    }
} 
