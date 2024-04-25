var foundEntities = false;
function entity_extraction_launcher(){
  //triggered by DOM element in text.php!!!!
  //remove the trigger button
  deleteIfExistsById('extractorTrigger'); 
  //determine the language of the text: 
  detectLanguage(languageOptions).then(function(result){
    displayLanguage(result);
    if(result){
      // do not try to detect entities if there's no language detected by the initial function.
      getEntities(languageOptions);
    }
    //displayEntities(foundEntities);
  });  
   document.getElementById('extractorProgress').classList.remove('hidden'); 
  //use the determined language to extract entities using the correct model. 
}
function getEntities(options){
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
      deleteIfExistsById('extractorProgress'); 
    }
  });
}

//make this a global variable.
var dropToNormalize = [];
var useNormalization = false;

//function to hide / unhide entities identified by the NER-tool that are already linked in the graph database.
//Event is attached to the hideUnhideEntities element in the DOM. Referencing is done by the onclick attribute
//in the DOM!!
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
  /**
   *  generates the sidebar in the DOM with the individual entity 
   * 
   *  Generates the highlight in the text as an automated annotation with all 
   *  required interactivity!
   */
  $counterTarget = $('#amountOfEntities');
  $modelTarget = $('#usedEntityModel');
  $entitiesTarget = $('#entitycontainer');
  $counterTarget.text(entities['meta']['found_entities_number']);
  $modelTarget.text(entities['meta']['used_model']);
  $foundEntities = entities['data'];
  $entitiesDisplay = document.createElement('div');
  $($entitiesDisplay).attr('id', 'showEntitiesHere');

  for (var i = 0; i < $foundEntities.length; i++) {
    //universal variables required in both cases;
    let et_start = $foundEntities[i]['startPos']; 
    let et_stop = $foundEntities[i]['endPos']; 
    let et_type = $foundEntities[i]['labelTex']; 

    //adding entity element to the side: 
    $singleEntity = document.createElement('p');
    $primaryTextSpan = document.createElement('span');
    $secondaryTextSpan = document.createElement('span');
    $singleEntityText = document.createTextNode($foundEntities[i]['text']);
    $($singleEntity).attr('data-start', et_start);
    $($singleEntity).attr('data-end', et_stop);
    $($singleEntity).attr('data-type', et_type);
    $($singleEntity).attr('data-stringExact', $foundEntities[i]['text']);
    //$($singleEntity).attr('data-stringNormalized');
    $primaryTextSpan.appendChild($singleEntityText);

    $singleEntity.appendChild($primaryTextSpan);
    $singleEntity.appendChild($secondaryTextSpan);
    $($primaryTextSpan).addClass('firstSpanElementOfEntity');
    $($primaryTextSpan).addClass('ignoreElementDepth');
    $($secondaryTextSpan).addClass('secondSpanElementOfEntity');
    $($singleEntity).addClass(et_type);
    $($singleEntity).addClass('anEntity');
     'anEntity'
    var clickForInfo = function(e){
      getInfoByClick(e);
    }
    $($singleEntity).click(clickForInfo);
    $entitiesDisplay.appendChild($singleEntity);

    //adding entity element to text as annotation_auto
    const ltrElements = document.querySelectorAll('.ltr');

  // Loop through each element
  ltrElements.forEach((element) => {
    const itercounter = parseInt(element.getAttribute('data-itercounter'), 10);

    // Check if itercounter is between 45 and 50
    if (itercounter >= et_start && itercounter <= et_stop) {
      // Add the 'highlighted' class
      element.classList.add('app_automatic', 'automatic_unstored');
      $(element).attr('data-entitytype', et_type);

      // Add a click event listener
      element.addEventListener('click', () => {
        // Custom function to handle the click event
        console.log(`Clicked on element with itercounter ${itercounter}`);
        //prompt to save entity :> onsave  =  generate UUID and return 
        //then triggerlookup!!!
        let xcoord = 1;
        let ycoord = 2;

        // You can replace the console.log with your desired action
      });
    }
  });


  }


  $entitiesTarget.append($entitiesDisplay);
}
