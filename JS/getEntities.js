var foundEntities = false;
// TODO: file is obsolete. ?? 
function getEntities(options){
  //TODO: code is opbsolete. 
  var language = options['ISO_code'];
  var nodeid = options['nodeid'];
  const param = {
    node: nodeid,
    lang: language
  };
  var getparam = jQuery.param(param);
  $.ajax({
    type:"GET",
    headers: {"Content-type": "application/x-www-form-urlencoded; charset=UTF-8"},
    url:"/AJAX/getEntities_async.php?"+getparam,
    success: function(result){
      foundEntities = result;
      displayEntities(foundEntities);
    }
  });
}

//make this a global variable.
var dropToNormalize = [];
var useNormalization = false;

//function to hide / unhide entities identified by the NER-tool that are already linked in the graph database.
//Event is attached to the hideUnhideEntities element in the DOM.
function hideUnhideEntities(){
  var count = 0;
  var state = document.getElementById('hideUnhideEntities').checked;
  //console.log(count, state);
  var ignore = [];
  if(state){
    for (key in storedAnnotations['relations']){
      var uqpos = storedAnnotations['relations'][key]['start']+'-'+storedAnnotations['relations'][key]['stop'];
      ignore.push(uqpos);
    }
    var ets = document.getElementsByClassName('anEntity');
    for (var et = 0; et < ets.length; et++){
      var entity = ets[et];
      var etstart = entity.dataset.start;
      var etstop = entity.dataset.end;
      if (ignore.includes(etstart+"-"+etstop)){
        entity.classList.add('hidden');
        count++;
      }
      document.getElementById('overlapcount').innerHTML = count+' ';
    }
  }else{
    var ets = document.getElementsByClassName('anEntity');
    for (var et = 0; et < ets.length; et++){
      ets[et].classList.remove('hidden');
    }
  }
}

//update the global dropToNormalize variable.
//function should return a list of every character(sequence) to be ignored in a sanitized string.
function update_NormalizationList(){
  var ignoreFieldInput = $("#normalizationList")[0].value.split(",");
  dropToNormalize = [];
  dropToNormalize = ignoreFieldInput.sort((b,a) => a.length - b.length);
}

//actual normalization of string: callable by passing string argument.
function normalizeString(stringIn){
  var stringOut = stringIn;
  for (var i = 0; i < dropToNormalize.length; i++){
    stringOut = stringOut.replace(dropToNormalize[i], '');
  }
  return stringOut;
}

//mass update the GUI.
function normalize_all(){
  update_NormalizationList();
  //gets the list of entities from the DOM
  var entities = $("#showEntitiesHere").children();
  //what needs to be done; set bool.
  if( $("#useNormalization")[0].checked ){
    useNormalization = true;
  }else{
    useNormalization = false;
  }
  //process each entity accordingly:
  for (var i = 0; i < entities.length; i++){
    var entity = entities[i];
    var entityPrimaryField = entities[i].getElementsByTagName('span')[0];
    if (useNormalization){
      //update UI and data-attribute: data-stringNormalized
      var normalizedString = normalizeString(entity.getAttribute('data-stringExact'));
      entityPrimaryField.innerHTML = normalizedString;
      $(entity).attr('data-stringNormalized', normalizedString) ;
    }else{
      //reset to default string: UI
      entityPrimaryField.innerHTML = entity.getAttribute('data-stringExact');
    }
  }
}

function displayEntities(entities){
  $counterTarget = $('#amountOfEntities');
  $modelTarget = $('#usedEntityModel');
  $entitiesTarget = $('#entitycontainer');
  $counterTarget.text(entities['meta']['found_entities_number']);
  $modelTarget.text(entities['meta']['used_model']);
  $foundEntities = entities['data'];
  $entitiesDisplay = document.createElement('div');
  $($entitiesDisplay).attr('id', 'showEntitiesHere');

  for (var i = 0; i < $foundEntities.length; i++) {
    $singleEntity = document.createElement('p');
    $primaryTextSpan = document.createElement('span');
    $secondaryTextSpan = document.createElement('span');
    $singleEntityText = document.createTextNode($foundEntities[i]['text']);
    $($singleEntity).attr('data-start', $foundEntities[i]['startPos']);
    $($singleEntity).attr('data-end', $foundEntities[i]['endPos']);
    $($singleEntity).attr('data-type', $foundEntities[i]['labelTex']);
    $($singleEntity).attr('data-stringExact', $foundEntities[i]['text']);
    //$($singleEntity).attr('data-stringNormalized');
    $primaryTextSpan.appendChild($singleEntityText);

    $singleEntity.appendChild($primaryTextSpan);
    $singleEntity.appendChild($secondaryTextSpan);
    $($primaryTextSpan).addClass('firstSpanElementOfEntity');
    $($primaryTextSpan).addClass('ignoreElementDepth');
    $($secondaryTextSpan).addClass('secondSpanElementOfEntity');
    $($singleEntity).addClass($foundEntities[i]['labelTex']);
    $($singleEntity).addClass('anEntity');
     'anEntity'
    var clickForInfo = function(e){
      getInfoByClick(e);
    }
    $($singleEntity).click(clickForInfo);
    $entitiesDisplay.appendChild($singleEntity);
  }


  $entitiesTarget.append($entitiesDisplay);
}
