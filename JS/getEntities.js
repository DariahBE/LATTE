/*
  js file used to interact with the LATTE connector. 
  There are are functions of other files referenced in here. 
*/

var foundEntities = false;
function entity_extraction_launcher(){
  //triggered by DOM element in text.php!!!!
  /*
    this is the main function for interacting with the LATTE connector. It will
    trigger all necessary AJAX-functions to get the backend to fetch the text,
    predict the language and extract all relevant Entities.
  */
  //remove the trigger button
  document.getElementById('connectorExpand').classList.remove('hidden'); 
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
  /*
    Part of the LATTE extraction pipeline: this function is triggered 
    after sucessful language detection. It will trigger the backend to
    extract entities from the text. Once extracted, entities will be displayed
    using the displayEntities function.
  */
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



//function to hide / unhide entities identified by the NER-tool that are already linked in the graph database.
//Event is attached to the hideUnhideEntities element in the DOM. Referencing is done by the onclick attribute
//in the DOM!!
/*
## Feature dropped: no auto-explore of hide/unhide action
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
}*/

/*
  ##Feature dropped: no normalization ##
//make this a global variable.
var dropToNormalize = [];
var useNormalization = false;
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
*/


function findItercounterRange(segmentId) {
  /**
   * Function that looks for the start and stop boundary of a node by their
   * given segment_id. Segment_ids are only used when visualizing LATTE 
   * connector entities. As an extra check, BLOCK all cases where there is no
   * element available in spans. 
   */
  // Find all span elements within the temporary div
  let spans = document.querySelectorAll('span[data-segment_id="' + segmentId + '"]');
  if(spans.length < 1){
    //early kill
    return {
      lowest: -1,
      highest: -1
  }; 
  }
  let lowestValue = Number.MAX_SAFE_INTEGER;
  let highestValue = Number.MIN_SAFE_INTEGER;
  let selectedText = ''; 
  
  spans.forEach(element => {
    console.log(element); 
      selectedText+=element.textContent;
      const iterCounter = parseInt(element.getAttribute('data-itercounter'));
      if (iterCounter < lowestValue) {
          lowestValue = iterCounter;
      }
      if (iterCounter > highestValue) {
          highestValue = iterCounter;
      }
  });

  return {
      lowest: lowestValue,
      highest: highestValue, 
      text: selectedText
  };
}

function updateSegmentedAnnotation(segment, uuid){
  /**
   * GETS an annotation with a specific segment id and: 
   * - replaces the segment ID it with a UUIDV4
   * - Removes the unstored class from the annotation
   * - Removes unneeded attributes from all segment elements. 
   * - simulates the click event for further disambiguation. (by calling the function!)
  */
      //REMOVE FROM ELEMENT: 
    //                    data-entitytype AND data-segment_id attributes
    
    let spans = document.querySelectorAll('span[data-segment_id="' + segment + '"]');
    spans.forEach(ltr => {
      deleteAttribute(ltr, 'data-entitytype');
      deleteAttribute(ltr, 'data-segment_id');
      ltr.removeEventListener('click', clickHandler);
      ltr.addEventListener('click', function(){loadAnnotationData();}); 
      ltr.classList.remove('automatic_unstored');   //remove class that indicates it is an unstored node
      ltr.classList.add('linked', 'underline', 'markedAnnotation');     //add classes to bring the layout and functionality in line with persistent app_automatic nodes. 
      ltr.setAttribute('data-annotation', uuid);    //add the UUID attribute to the node. 
    });
  loadAnnotationData(uuid); //call the function that's normally triggered by an onclick event. 
}

function disableButtonByElemId(elemid){
  let button = document.getElementById(elemid);
  button.disabled = true;
  button.classList.add('disabled'); //disable the button
}


function deleteAttribute(fromElement, attributeName){
    fromElement.removeAttribute(attributeName); 
}

function persistSuggestionOfLatteConnector(segment){
  /**
   * When the user clicks the Store button from the suggestion box, this function 
   * is triggered. 
   * IF the user is logged in             AND
   * IF the user passes permissionscheck
   * THEN
   * the node that triggered the event gets stored as a persistent node in the 
   * database and receives a unique UUIDV4 identifier. An automatic trigger
   * then takes the user to the disambiguation stage
   */
  //check! ==>  global login is available
  if(globalLoginAvailable){
    //global login !== sufficient rights set: check that here: 
    //if the user's rights are lacking, the token manager dies  and refuses to assign
    //a csrf token ==> no token = no storage!
    //use segment to find the lowest and highest data-itercounter; these are the 
    //start and stop of the annotation
    let bounds = findItercounterRange(segment); 
    let start = bounds['lowest'];
    let stop = bounds['highest'];
    globalSelectionText = bounds['text']
    disableButtonByElemId('suggestionbox_saveButton'); 
    disableButtonByElemId('suggestionbox_dropButton');     
    if (start > -1 && stop >= start){
      //check login and permissions.
      fetch("/AJAX/getdisposabletoken.php?task=1")    
      .then(response => response.json())
      .then(data => {
        const token = data;
        fetch("/AJAX/persist_auto_annotation.php?texid="+languageOptions.nodeid+"&starts="+start+"&stops="+stop+"&token="+data)
          .then(response => response.json())
          .then(data => {
            //the storage method for automatic annotations does allow for arrays, however, in this 
            //function it will only be called with instructions for a single annotation node. So we
            //can ask the UUIDV4 back after creation and use it to trigger a click event.
            let uuid = data[0];
            updateSegmentedAnnotation(segment, uuid); 
            //simulate an event trigger by using the uuid variable and pass it
            //to the function that is normally triggered by the click event!
          })
          /*
          fetch("/AJAX/variants/make.php?varlabel=" + writtenValue + "&entity=" + relatedET + "&token=" + token)
              .then(response => response.json())
              .then(data => {
                  let nid = data['data']['nid'];
                  let uuid = data['data']['uuid'];
                  document.getElementById('variantInputBox').value = '';
                  classScope.addVariantInBox(writtenValue, uuid, nid, classScope.userstate);
                  resolve(spellingVariantMainBox); // Resolve promise when operation is complete
              })
              .catch(error => {
                  reject(error); // Reject promise if there is an error
              });*/
      })
      .catch(error => {
          reject(error); // Reject promise if there is an error
      });
    }

  }

}


function generateRandomIdAttribute(l = 12){
  //returns a random l-character long random hexadecimal string 
  //is used as identifier for LATTE connector highlights: 
  const chars = '0123456789ABCDEF';
  let id = '';
  for (let i = 0; i < l; ++i) {
      id += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return id;
}
function displayEntities(entities){
  /**
   * 
   * WILL NOT UPDATE THE DOM WITH PICKED UP ENTITIES THAT HAVE MATCHING BORDERS
   * (start - stop)ALREADY IN THE TEXT!
   * 
   * Triggered after successfull entity extraction of the text. Will visualize
   * the entities in the text by adding a class to the elements that fall within
   * the start stop range. 
   */
  // Function to check if any element has start and stop
  function checkRange(obj, start, stop) {
    return Object.keys(obj).some(key => obj[key].start === start && obj[key].stop === stop);
  }
  
  $counterTarget = $('#amountOfEntities');
  $modelTarget = $('#usedEntityModel');
  $entitiesTarget = $('#entitycontainer');
  $counterTarget.text(entities['meta']['found_entities_number']);
  $modelTarget.text(entities['meta']['used_model']);
  $foundEntities = entities['data'];
  let allAnnotations = {...automatic_annotations, ...storedAnnotations.relations};

  for (var i = 0; i < $foundEntities.length; i++) {
    //universal variables required in both cases;
    let et_start = $foundEntities[i]['startPos']; 
    let et_stop = $foundEntities[i]['endPos']; 
    let et_type = $foundEntities[i]['labelTex']; 
    if (checkRange(allAnnotations, et_start, et_stop)){
      continue; 
    }
    //adding entity element to text as annotation_auto
    const ltrElements = document.querySelectorAll('.ltr');

  // Loop through each element
  let segment_id = generateRandomIdAttribute()
  ltrElements.forEach((element) => {
    const itercounter = parseInt(element.getAttribute('data-itercounter'), 10);
    if (itercounter >= et_start && itercounter <= et_stop) {
      // Add the 'highlighted' class: default color from config file for automatic annotations
      element.classList.add('app_automatic', 'automatic_unstored');
      $(element).attr('data-entitytype', et_type);
      $(element).attr('data-segment_id', segment_id); 
      // Add a click event listener
      element.addEventListener('click', clickHandler);
    }
  });
  }

  //$entitiesTarget.append($entitiesDisplay);
}
// Add the event listener
const clickHandler = () => {
  //trigger when clicking auto_anntoation element. 
  makeSuggestionBox();
};

