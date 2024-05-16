function getInfoFromBackend(url){
  var myPromise = new Promise ((resolve, reject)=>{
    $.ajax({
      type:"GET",
      headers: {"Content-type": "application/x-www-form-urlencoded; charset=UTF-8"},
      url:url,
      success: function(result){
        resolve(result);
      }
    });

  })
  return myPromise;
}

/*
//Obsole code, only required for the entity side panel provided by the 
LATTE connector. 
function showInfoFromBackend(info, elem){
  var matchingRecords = info['meta']['entities'];
  var nodes = info['nodes'];
  var edges = info['edges'];
  var backendHandler = function(e){
    e.stopPropagation();
    generateNodeOverlayWindow(e);
  };
  elem.target.getElementsByTagName('span')[1].innerHTML='<i class="fa fa-eye ignoreElementDepth"></i>'+matchingRecords;
  elem.target.setAttribute('data-retrievedNodes', JSON.stringify(nodes));
  elem.target.setAttribute('data-retrievedEdges', JSON.stringify(edges));
  elem.target.getElementsByClassName('secondSpanElementOfEntity')[0].removeEventListener('click', backendHandler);
  elem.target.getElementsByClassName('secondSpanElementOfEntity')[0].addEventListener('click', backendHandler);
}*/

/*
function getInfoByClick(e){
  extracts stored info from the database for a node that's stored with 
  same spellng as the click-event comes from.
  Triggers are applied to the side column where matched entities are present
  after analysis by LATTE connector. 
  //depends on use of normalization.
  var nodetype = e.target.getAttribute('data-type');
  var strValue = e.target.getAttribute('data-stringExact');
  /*
  if(useNormalization){
    strValue = e.target.getAttribute('data-stringNormalized');
  }* /
  $baseURL = '/AJAX/getEntitySuggestion.php?';
  $parameters = {
    'type':nodetype,
    'value':strValue,
    'casesensitive':false
  };
  $sendTo = $baseURL+jQuery.param($parameters);
  getInfoFromBackend($sendTo)
  .then((data)=>{
    showInfoFromBackend(data, e);
  })
}*/
