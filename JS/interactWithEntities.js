let datamode = null;
let globalLoginAvailable = false; 
let globalAnnoInteractId = false;

function checklogin() {
  return new Promise((resolve, reject) => {
      $.ajax({
          url: "/user/AJAX/profilestate.php",
          success: function(result) {
              resolve(result['valid']);
          },
          error: function(error) {
              reject(error);
          }
      });
  });
}
checklogin()
  .then(valid => {
    globalLoginAvailable = valid;
  })
  .catch(error => {
    globalLoginAvailable = false; 
  })


function getInfoFromBackend(url) {
  var myPromise = new Promise((resolve, reject) => {
    $.ajax({
      type: "GET",
      headers: { "Content-type": "application/x-www-form-urlencoded; charset=UTF-8" },
      url: url,
      success: function (result) {
        resolve(result);
      }
    });

  })
  return myPromise;
}

function findRelatedTexts(neoID) {
  //do an AJAX-call and COUNT() to how many TEXT-nodes this ET is connected: 
  function isPlural(input) {
    if (input != 1) { return 's' } else { return '' }
  }
  fetch("/AJAX/connected_texts.php?id=" + neoID)
    .then((response) => response.json())
    .then((data) => {
      var showToUser = `Mentioned ${data.Annotations} time${isPlural(data.Annotations)} in ${data.Texts} text${isPlural(data.Texts)}`;
      document.getElementById("relatedTextStats").innerHTML = `<p>${showToUser}</p>`;
    });
}
/*
function decideOnEdit(protected, level){
  console.log(protected, level);
  if(protected){
    return false;
  }
  else if (level > 1 ){
    return true;
  }else{
    return false;
  }
}

function waitForElement(selector) {
  return new Promise(resolve => {
    if (document.querySelector(selector)) {
      return resolve(document.querySelector(selector));
    }

    const observer = new MutationObserver(mutations => {
      if (document.querySelector(selector)) {
        resolve(document.querySelector(selector));
        observer.disconnect();
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  });
}
*/

function createStableLinkingBlock(nodeid, stableURI) {
  //generates a link to the graph explorer page
  //and to the stable URI 
  //returns a DIV with both link elements embedded in.
  var linkToGraphExplorer = '/explore/' + nodeid;
  var linkElement = document.createElement('a');
  linkElement.setAttribute('href', linkToGraphExplorer);
  linkElement.setAttribute('target', '_blank');
  imgElement = document.createElement('img');
  imgElement.src = '/images/graphExplore.png';
  linkElement.appendChild(imgElement);
  var linkToStablePage = document.createElement('a');
  linkToStablePage.setAttribute('href', stableURI);
  linkToStablePage.setAttribute('target', '_blank');
  stableImgElement = document.createElement('i');
  stableImgElement.classList.add('fas', 'fa-anchor');
  linkToStablePage.appendChild(stableImgElement);
  subdivGateway = document.createElement('div');
  subdivGateway.classList.add('flex', 'flex-row');
  subdivGateway.setAttribute('id', 'stableBox');
  subdivGateway.appendChild(linkElement);
  subdivGateway.appendChild(linkToStablePage);
  return subdivGateway;
}



async function fetchDisplayOptions(url) {
  try {
      let response = await fetch(url, { method: 'GET' });

      if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
      }

      let data = await response.json(); // Parse JSON 
      return data;
  } catch (error) {
      console.error('Error fetching data:', error);
  }
}

async function createEditRemoveBox(etnodeid, annonodeid){ 
  /*
    creates a box with edit and delete buttons for the entity and annotation.
    depending on the user rights and ownership of the entity/annotation.
    the buttons are shown or hidden.
  */
  let data = await fetchDisplayOptions('/AJAX/hasRights.php?id_e='+etnodeid+'&id_a='+annonodeid);

  let etdelete = data['et']['delete'];
  let etupdate = data['et']['update'];
  let annodelete = data['anno']['delete'];
  let annoupdate = data['anno']['update'];

  let subdivGateway = document.createElement('div');
  if(!(globalLoginAvailable)) { return subdivGateway; }

  let annotationPart = document.createElement('div'); 
  if (annonodeid !== false) {
    let annoEditLink = '/crud/edit.php?id=' + annonodeid;
    let annoDeleteLink = '/crud/delete.php?id=' + annonodeid;
    let annoeditElement = document.createElement('a'); 
    annoeditElement.setAttribute('href', annoEditLink); 
    let annodeleteElement = document.createElement('a'); 
    annodeleteElement.setAttribute('href', annoDeleteLink);
    
    annotationPart.classList.add('w-full');
    let annotationSectionHeader = document.createElement('H4'); 
    annotationSectionHeader.appendChild(document.createTextNode('Edit annotation: '));
    annotationPart.appendChild(annotationSectionHeader); 
    annotationSectionHeader.classList.add('font-bold', 'text-lg', 'w-full');

    let annotationDelete = document.createElement('button'); 
    annotationDelete.classList.add('btn', 'rounded', 'text-white', 'font-bold', 'py-2', 'px-4', 'bg-red-500', 'hover:bg-red-700');
    annotationDelete.appendChild(document.createTextNode('Delete'));

    let annotationEdit = document.createElement('button'); 
    annotationEdit.classList.add('btn', 'rounded', 'text-white', 'font-bold', 'py-2', 'px-4', 'bg-blue-500', 'hover:bg-blue-700');
    annotationEdit.appendChild(document.createTextNode('Edit'));

    annoeditElement.appendChild(annotationEdit); 
    annodeleteElement.appendChild(annotationDelete); 
    if (annoupdate) {
      annotationPart.appendChild(annoeditElement);
    }
    if (annodelete) {
      annotationPart.appendChild(annodeleteElement); 
    }
  }

  let entityEditLink = '/crud/edit.php?id=' + etnodeid;
  let entityDeleteLink = '/crud/delete.php?id=' + etnodeid;
  let etEditElement = document.createElement('a'); 
  etEditElement.setAttribute('href', entityEditLink); 
  let etDeleteElement = document.createElement('a'); 
  etDeleteElement.setAttribute('href', entityDeleteLink); 
  let entityPart = document.createElement('div'); 
  entityPart.classList.add('w-full');

  let entitySectionHeader = document.createElement('H4'); 
  entitySectionHeader.appendChild(document.createTextNode('Edit entity: '));
  entitySectionHeader.classList.add('font-bold', 'text-lg', 'w-full');
  entityPart.appendChild(entitySectionHeader);

  let entityDelete = document.createElement('button');
  entityDelete.classList.add('btn', 'rounded', 'text-white', 'font-bold', 'py-2', 'px-4', 'bg-red-500', 'hover:bg-red-700');
  entityDelete.appendChild(document.createTextNode('Delete'));

  let entityEdit = document.createElement('button'); 
  entityEdit.classList.add('btn', 'rounded', 'text-white', 'font-bold', 'py-2', 'px-4', 'bg-blue-500', 'hover:bg-blue-700');
  entityEdit.appendChild(document.createTextNode('Edit'));

  etDeleteElement.appendChild(entityDelete);
  etEditElement.appendChild(entityEdit);
  if (etupdate) {
    entityPart.appendChild(etEditElement);
  }
  if (etdelete) {
    entityPart.appendChild(etDeleteElement);
  }

  subdivGateway.setAttribute('id', 'editBox');
  if (annoupdate || annodelete){
    subdivGateway.appendChild(annotationPart);
  }
  if(etupdate || etdelete){
    subdivGateway.appendChild(entityPart);
  }
  globalAnnoInteractId = false; 
  return subdivGateway;
}


function writeField(key, data, protected, structure) {
  //console.log('Method A', key, data, protected);
  //if(!(decideOnEdit(protected, rights))){
  var field = document.createElement('p');
  var fieldkey = document.createElement('span');
  fieldkey.classList.add('labelKey', 'font-bold');
  var fieldType = structure[key] !== undefined ? structure[key][1] : 'string';
  var keytex = structure[key] !== undefined ? structure[key][0] : key;
  var fieldkeyString = document.createTextNode(keytex + ': ');
  //data can be false for boolean fields. In this case, always add the
  //fieldkeystring and fieldkey, but do that in the bool-code section. 
  if (data !== false && data !== '' && fieldType !== 'bool') {
    fieldkey.appendChild(fieldkeyString);
    field.appendChild(fieldkey);
  }
  if (fieldType === 'bool') {
    //adding fieldkey for boolean code
    fieldkey.appendChild(fieldkeyString);
    field.appendChild(fieldkey);
    var fieldvalue = document.createElement('select');
    //writefield is used for display, not update
    fieldvalue.setAttribute('disabled', true);
    var optionTrue = document.createElement('option');
    optionTrue.value = 'true';
    optionTrue.textContent = 'True';
    var optionFalse = document.createElement('option');
    optionFalse.value = 'false';
    optionFalse.textContent = 'False';
    fieldvalue.appendChild(optionTrue);
    fieldvalue.appendChild(optionFalse);
    fieldvalue.value = data ? 'true' : 'false'; // set the selected option based on the data
    field.appendChild(fieldvalue);
  } else if (fieldType === 'uri') {
    if (data != ''){
      var fieldvalue = document.createElement('a');
      fieldvalue.classList.add('externalURILogo');
      fieldvalue.setAttribute('href', data);
      fieldvalue.setAttribute('target', '_blank');
      fieldvalue.appendChild(document.createTextNode(data));
      field.appendChild(fieldvalue);
      fieldvalue.setAttribute('type', 'url');
    }
  } else if (fieldType === 'wikidata'){
    var fieldvalue = document.createElement('a');
    fieldvalue.setAttribute('href', 'https://www.wikidata.org/wiki/'+data);
    fieldvalue.setAttribute('target', '_blank');
    fieldvalue.classList.add('externalURILogo');
    fieldvalue.appendChild(document.createTextNode(data));
    field.appendChild(fieldvalue);
    fieldvalue.setAttribute('type', 'url');
  }else {
    var fieldvalue = document.createElement('span');
    var fieldvalueString = document.createTextNode(data);
    fieldvalue.appendChild(fieldvalueString);
    field.appendChild(fieldvalue);
    fieldvalue.setAttribute('type', 'text');
    fieldvalue.value = data;
    field.appendChild(fieldvalue);
  }
  //console.log('created textfield', field);
  return field;
}

let auto_annotation_internal_id = NaN; 
function showdata(data) {
  /**       FUNCTION TRIGGERED BY INTERACTING WITH ENTITIES: 
   *  - there is a marked annotation in the databse. 
   *  - if it is connected to an entity; it is shown too. 
   *  - entity has properties such as WIKIDATA, VARIANT, ETID (Kb relations. ) 
   */
  //set the global datamode: 
  datamode = data['mode'];
  toggleSlide(1);
  //console.log('creating side skelleton'); 
  createSideSkelleton(); 

  let et = data.entity?.[0]?.neoID ?? false;
  //let rst = undefined; 


  var annotationTarget = document.getElementById('slideoverDynamicContent');
  //superimpose the slideover on top of the navbar: 
  annotationTarget.classList.add("z-50");
  //bugpatch
  let exists = true;
  var gateWay = document.getElementById('neobox') || (function() {
    var newGateWay = document.createElement('div');
    newGateWay.setAttribute('id', 'neobox');
    exists = false; 
    return newGateWay;
  })();
  var statsTarget = document.createElement('div');
  statsTarget.setAttribute('id', 'relatedTextStats');
  statsTarget.classList.add('text-gray-600', 'w-full', 'm-2', 'p-2', 'left-0');
  gateWay.appendChild(statsTarget);
  //var authorData = data['author']; //not required. 
  var annotationData = data['annotation']['properties'];
  //sends the node neoID (unstable, do not use for identifying purposes on exposed API's):
  if (datamode === 'controll') {
    findRelatedTexts(data['entity'][0]['neoID']);
  } else if (datamode === 'automated'){
    // console.warn('extract start and stop from automated ets. '); 
    // console.log(data.annotation.properties[startcode][1])
    globalSelectionEnd = data.annotation.properties[stopcode][1];
    globalSelectionStart = data.annotation.properties[startcode][1];
    auto_annotation_internal_id = data['neo_id_of_auto_anno']; 
  }
  var annotationStructure = data['annotationFields'];
  var annotationExtraFields = Object.keys(data['annotationFields']) || false;

  //work with the Annotations:
  let annotationHeader = createDivider('Annotation: '); 
  annotationTarget.appendChild(annotationHeader); 
  Object.keys(annotationData).forEach(key => {
    var row = annotationData[key];
    var rowkey = row[0];
    var rowdata = row[1];
    var protected = row[2];
    if (annotationExtraFields) {
      var idx = annotationExtraFields.indexOf(rowkey);  //-1 if not exists.
      if (idx >= 0) {
        annotationExtraFields.splice(idx, 1);
      }
    }
    //puts the annotation metadata in the annotationtarget element.
    var fieldFormatted = writeField(rowkey, rowdata, protected, annotationStructure);
    annotationTarget.appendChild(fieldFormatted);
    console.log('annotation values: '); 
    console.log(rowkey, rowdata, protected, annotationStructure); 
  
  });
  //for all annotationExtraFields create a new editable field:
  for (var i = 0; i < annotationExtraFields.length; i++) {
    annotationTarget.appendChild(writeField(annotationExtraFields[i], '', false, annotationStructure));
  }
  //show the type of the annotation as a header entry: 
  if (datamode === 'controll') {
    let etType = createDivider('Entity: ' + data['entity'][0]['type']); 
    //var etType = document.createElement('h3');
    var etStable = data['entity'][0]['stableURI'];
    //etType.classList.add('font-bold', 'text-lg', 'w-full', 'items-center', 'flex', 'justify-center');
    //var etTypeText = document.createTextNode('Entity: ' + data['entity'][0]['type']);
    var entityStructure = data['entityFields'];
    let etpropdiv = document.createElement('div'); 
    etpropdiv.classList.add('etPropContainer'); 
    Object.keys(data['entity'][0].properties).forEach(key => {
      var row = data['entity'][0].properties[key];
      let displayName = row[0];
      let displayValue = row[1];
      etpropdiv.appendChild(writeField(displayName, displayValue, true, entityStructure)); 
    });
    //etType.appendChild(etTypeText);
    annotationTarget.appendChild(etType);
    annotationTarget.appendChild(etpropdiv); 
    let neoid = data['entity'][0]['neoID'];
    checklogin()
    .then(valid => {
        kb = new KnowledgeBase(neoid, valid);
    })
    .catch(error => {
      kb = new KnowledgeBase(false, false);
    })
    gateWay.appendChild(createStableLinkingBlock(neoid, etStable));
    createEditRemoveBox(neoid, globalAnnoInteractId)
    .then(divelement => {
      console.log('divelement', divelement);
      gateWay.appendChild(divelement);
    })
    .catch(error => console.error("Error creating element:", error));
    if(exists){
      //patch
      annotationTarget.appendChild(gateWay);
    }
    //display the variant data: 
    spellingVariantDOMReturn = new SpellingVariant(data['variants'], neoid, globalLoginAvailable);
    /*
    checklogin()
      .then(valid => {
        //rst = valid['valid'];
        spellingVariantDOMReturn = new SpellingVariant(data['variants'], neoid, valid);
      })
      .catch(error => {
        spellingVariantDOMReturn = new SpellingVariant(data['variants'], neoid, false);
      })*/
    //displayET_variant(data['variants'], neoid);
    //With the type known: look up if there's a wikidata attribute: 
    let wdLabelAccordingToModel = Object.keys(entityStructure).filter(ar => entityStructure[ar][1] == 'wikidata')[0] || false;  
    if (wdLabelAccordingToModel !==  false && data['entity'][0].properties[wdLabelAccordingToModel] !== undefined ) {
      /*It's not always possible to assign Q-ids to an entity; so it could be undefined. Only run this block if there's a link to WD */
      let qid = data['entity'][0].properties[wdLabelAccordingToModel][1]; 
      var wd = new wikibaseEntry(qid, wdProperties, 'slideover', 'qid');
      wd.getWikidata()
        .then(function () { wd.renderEntities(qid) });
    }
  } else if (datamode === 'automated') {
    // improved code by relying on the global! 
    //  value is assigned to globalSelectionText by using the annotation trigger. 
    
    $ld_count = $('#max_ld'); 
    $baseURL = '/AJAX/getEntitySuggestion.php?';
    $parameters = {
      'type': '',    //type is empty as there was no pickup by NERtool
      'value': globalSelectionText,
      'casesensitive': false, 
      'allow_levenshtein': $('#use_ld').is(":checked"), 
      'levenshtein_items': $('#max_ld').val()
    };
    $sendTo = $baseURL + jQuery.param($parameters);
    //there's no need to keep the call to the suggestionbox during the conversion process!
    //makeSuggestionBox();
    getInfoFromBackend($sendTo)
    .then((data) => {
        loadIntoSuggestionBox(data, globalSelectionStart, globalSelectionEnd);
      })
  }
}

function handleError(e) {
  /**
   * Creates a notification modal for the end user when something goes wrong, 
   * the error itself gets logged in the console as an error. 
   */
  console.error('An unexpected error occurred.');
  console.error(e);

  // Create the modal element
  const modal = document.createElement('div');
  modal.classList.add('fixed', 'z-50', 'inset-0', 'overflow-y-auto', 'px-4', 'py-6', 'sm:px-0');

  // Create the modal overlay element
  const overlay = document.createElement('div');
  overlay.classList.add('fixed', 'inset-0', 'bg-gray-500', 'opacity-75');

  // Create the modal content element
  const content = document.createElement('div');
  content.classList.add('mx-auto', 'my-auto', 'relative', 'bg-white', 'rounded-lg', 'px-4', 'pt-5', 'pb-4', 'overflow-hidden', 'shadow-xl', 'transform', 'sm:my-8', 'sm:align-middle', 'sm:max-w-lg', 'sm:w-full');

  // Create the modal header element
  const header = document.createElement('div');
  header.classList.add('mb-4');
  const headerText = document.createElement('h3');
  headerText.classList.add('text-lg', 'leading-6', 'font-medium', 'text-gray-900');
  headerText.textContent = 'An error occurred';
  header.appendChild(headerText);

  // Create the modal body element
  const body = document.createElement('div');
  const bodyText = document.createElement('p');
  bodyText.classList.add('text-sm', 'text-gray-500');
  bodyText.textContent = 'An unspecified error occurred. The data was missing or could not be displayed.';
  body.appendChild(bodyText);

  // Create the modal footer element
  const footer = document.createElement('div');
  footer.classList.add('mt-5', 'sm:mt-6', 'sm:grid', 'sm:grid-cols-2', 'sm:gap-3', 'sm:grid-flow-col', 'sm:justify-between');

  // Create the close button element
  const closeButton = document.createElement('button');
  closeButton.classList.add('inline-flex', 'justify-center', 'rounded-md', 'border', 'border-transparent', 'bg-red-600', 'px-4', 'py-2', 'text-base', 'font-medium', 'text-white', 'hover:bg-red-700', 'focus:outline-none', 'focus:ring-2', 'focus:ring-red-500', 'focus:ring-offset-2', 'focus:ring-offset-gray-50');
  closeButton.textContent = 'Close';
  closeButton.addEventListener('click', () => {
    modal.remove();
  });

  // Append the elements to the modal
  content.appendChild(header);
  content.appendChild(body);
  footer.appendChild(closeButton);
  content.appendChild(footer);
  modal.appendChild(overlay);
  modal.appendChild(content);

  // Append the modal to the body
  document.body.appendChild(modal);
}

function loadAnnotationData(annotationID = false) {

  console.log(globalLoginAvailable); 
  var relatedAnnotations = []; 
  if (!(annotationID)){
    //get annotationID in case of clickevent trigger: find the source of the event. 
    var eventsource = event.source || event.target;
    //event.preventDefault();
    relatedAnnotations = eventsource.dataset.annotation.split(',');
    annotationID = relatedAnnotations[0];
  }
  //otherwise the annotationID is given as of the function call parameter(in case of
  //  programatically triggering the event). 
  // to handle multiple overlapping annotations do a check here: is the even triggered by an overlap (2 or more) or not (1 or 0). 
  if(relatedAnnotations.length <= 1){
    getInfoFromBackend("/AJAX/resolve_annotation.php?annotation=" + annotationID)
    .then((data) => {
      if (data['code']==-1){
        handleError(''); 
      }else{
        globalAnnoInteractId = data['annotation']['neoid']; 
        //console.log(data); 
        showdata(data);
        updateState('State', 'An annotated entity was selected, you can now see the data held in the database.'); 
      }
    })
    .catch(err => handleError(err) );
  }
}

function addInteractionToEntities() {
  var links = document.getElementsByClassName('linked');
  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function () {
      loadAnnotationData();
    })
  }
};



function createDivider(string){
  /**
   * USED to structure the entity sidebar with extended info for
   * wikidata, variants and entity.  
   */
  const divider = document.createElement('h3');
  const dividerstring = document.createTextNode(string);
  divider.appendChild(dividerstring);
  divider.classList.add('font-bold', 'text-lg', 'w-full', 'items-center', 'flex', 'justify-center'); 
  return divider;
}

// function showDBInfoFor(id, extra = '') {
//   // TO DO dormant code (cleanup allows for deletion!) (ok)
//   /*
//   * Uses the internal NEOID identifier to fetch all information of a given entity.
//   * given info includes: variants, properties, stable id, label and the datamodel!
//   * THIS is a function specific to the disambiguation process coming from showhit()-calls. 
//   * Needs to be extende so that it shows the actual data in the DOM. 
//   */
//   let extended = '';
//   if (extra) {
//     extended = '&extended=1'
//   }
//   // shows all data there's stored about it.
//   getInfoFromBackend('/AJAX/getETById.php?id=' + id + extended)   //irrelevant at this point!
//     .then((data) => {
//       //process entity information: 
//       //  Use the order defined by the model to show properties: 
//       const model = data['extra']['model']; 
//       const info = data['props'];
//       // Iterate over properties
//       //let reduced = info.map(sublist => sublist[0]);    //OK: 
//       /*for (let mod of Object.values(model)){ 
//         let modName = mod[0]; 
//         if(reduced.indexOf(modName)>-1){
//           //let domElement = document.createElement('span');
//           console.log('PROP FOUND: ', modName, info[reduced.indexOf(modName)]);
//         }
//       }*/

//       // for (let i = 0; i < model.length; i++) {
//       //   let modelBlock = model[i]; 
//       //   let domName = modelBlock[0]; 
//       //   let domType = modelBlock[1]; 
//       // }
//       // for (let i = 0; i < info.length; i++) {
//       //   let infoBlock = info[i];
//       //   let blockName = infoBlock[0];
//       //   let blockData = infoBlock[1];
//       //   console.log(infoBlock);
//       // }
//       //process: variants
//       const variants = data['variantSpellings'];
//       const uri = data['stable'];
//       neoVarsToDom(variants, 1); 
//       //make varbox visible!
//       document.getElementById('embeddedSpellingVariants').classList.remove('hidden'); 
//       //showing entity in the DOM: 
//       //1:  Make empty
//       let proptarget = document.getElementById('displayHitEt'); 
//       proptarget.innerHTML = ''; 
//       //show the type of entity that has a potential match!
//       let typeOfEt = data.extra.label;
//       proptarget.appendChild(createDivider('Entity: '+typeOfEt)); 
//       //2:  use writeField function!
//       Object.values(data['props']).forEach((prop) => {
//         console.log('property: ', prop); 
//         let propKey = prop[0];
//         let propVal = prop[1];
//         console.log(propKey, propVal); 
//         let subelem = writeField(propKey, propVal, true, data.extra.model);
//         console.log(subelem); 
//         proptarget.appendChild(subelem); 
//       });
//       //TO DO: option to connect ET to current selection is still missing!! // not required, dormant code!

//       let referenceNode = document.getElementById('relatedTextStats').parentElement;
//       //remove the stable block if it exists: 
//       let stableBox = document.getElementById('stableBox');
//       if (stableBox) { stableBox.remove(); }
//       referenceNode.appendChild(createStableLinkingBlock(id, uri));
//       referenceNode.appendChild(createEditRemoveBox(id, globalAnnoInteractId));
//     });

// }

$(document).ready(function () {
  addInteractionToEntities();
});
