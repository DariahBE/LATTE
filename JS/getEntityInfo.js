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
}

function getInfoByString(string, nodetype){
  console.log(string, nodetype);
  $baseURL = '/AJAX/getEntitySuggestion.php?';//?type=Place&value=constantinople&casesensitive=false'
  $parameters = {
    'type':nodetype,
    'value':string,
    'casesensitive':false
  };
  $sendTo = $baseURL+jQuery.param($parameters);
  getInfoFromBackend($sendTo)

  .then((data)=>{
    console.log(data);
    showInfoFromBackend(data);
  })
}

function getInfoByClick(e){
  //depends on use of normalization.
  console.log('detected Element click');
  var nodetype = e.target.getAttribute('data-type');
  if(useNormalization){
    var strValue = e.target.getAttribute('data-stringNormalized');
  }else{
    var strValue = e.target.getAttribute('data-stringExact');
  }
  $baseURL = '/AJAX/getEntitySuggestion.php?';//?type=Place&value=constantinople&casesensitive=false'
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
}
/*
function getInfoByID(id){


}
*/
