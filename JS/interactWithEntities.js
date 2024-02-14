let datamode = null;

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
}*/

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

/*
function fieldWriter(key, val, keyclasses = [], valclasses = []){
  / *  *   
  Used to be a problem, replaced by writeField function!
  integration of fieldwriter is not working!
   * returns a P element with two span elements embedded in.
   * each of the spanelements receives either the keyclasses or valclasses if defined
   * /
  let p = document.createElement('p'); 
  let keyspan = document.createElement('span');
  keyspan.appendChild(document.createTextNode(key));
  let valspan = document.createElement('span');
  valspan.appendChild(document.createTextNode(val));
  keyspan.classList.add(...keyclasses);
  valspan.classList.add(...valclasses);
  p.appendChild(keyspan); 
  p.appendChild(valspan); 
  return p; 
}*/

function showETProps(props, structure){
  let div = document.createElement('div'); 
  props.forEach(prop => {
    let displayName = prop[0];
    let displayValue = prop[1];
    // TODO: var unused, needs to be set as data attribute!!
    let displayType = prop[2];
    let subelem = writeField(displayName, displayValue, true, structure);  //BUG: structure missing in call!!
    //let subelem = fieldWriter(displayName, displayValue, ['labelKey', 'font-bold'], []); 
    // let key = document.createElement('span');
    // key.classList.add('font-weight-bold')
    // let val = document.createElement('span');
    // key.appendChild(document.createTextNode(displayName));
    // val.appendChild(document.createTextNode(displayValue));
    div.appendChild(subelem);
  });
  return div;  
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
  if (data !== false && data !== '') {
    fieldkey.appendChild(fieldkeyString);
    field.appendChild(fieldkey);
  }
  if (fieldType === 'bool') {
    //TODO make boolfield dropdown. 
    console.log('setting behaviour for bool field'); 
    var fieldvalue = document.createElement('span');
    var fieldvalueString = document.createTextNode(data);
    fieldvalue.appendChild(fieldvalueString);
    field.appendChild(fieldvalue);
    fieldvalue.setAttribute('type', 'boolean');
  } else if (fieldType === 'uri') {
    var fieldvalue = document.createElement('a');
    fieldvalue.setAttribute('href', data);
    fieldvalue.setAttribute('target', '_blank');
    fieldvalue.appendChild(document.createTextNode(data));
    field.appendChild(fieldvalue);
    fieldvalue.setAttribute('type', 'url');
  } else {
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
  //set the global datamode: 
  datamode = data['mode'];
  toggleSlide(1);
  //console.log('creating side skelleton'); 
  createSideSkelleton();
  var annotationTarget = document.getElementById('slideoverDynamicContent');
  //superimpose the slideover on top of the navbar: 
  annotationTarget.classList.add("z-50");
  var gateWay = document.createElement('div');
  gateWay.setAttribute('id', 'neobox');
  var statsTarget = document.createElement('div');
  statsTarget.setAttribute('id', 'relatedTextStats');
  statsTarget.classList.add('text-gray-600', 'w-full', 'm-2', 'p-2', 'left-0');
  //annotationTarget.innerHTML = ''; (TODO: delete test is this okay? so far so good)
  gateWay.appendChild(statsTarget);
  //TODO: test if this was a good idea to delete, so far it all looks okay. goal is to deduplicate code!!
  /*var variantsTarget = document.createElement('div');
  variantsTarget.setAttribute('id', 'etVariantsTarget');
  variantsTarget.classList.add('text-gray-600', 'w-full', 'm-2', 'p-2', 'left-0');
  gateWay.appendChild(variantsTarget);*/
  var authorData = data['author'];
  var annotationData = data['annotation']['properties'];
  //sends the node neoID (unstable, do not use for identifying purposes on exposed API's):
  if (datamode === 'controll') {
    findRelatedTexts(data['entity'][0]['neoID']);
  } else if (datamode === 'automated'){
    console.warn('extract start and stop from automated ets. '); 
    console.log(data.annotation.properties.starts[1])
    globalSelectionEnd = data.annotation.properties.stops[1];
    globalSelectionStart = data.annotation.properties.starts[1];
    auto_annotation_internal_id = data['neo_id_of_auto_anno']; 
  }
  var annotationStructure = data['annotationFields'];
  var annotationExtraFields = Object.keys(data['annotationFields']) || false;

  //work with the Annotations:
  console.log(annotationData);
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
    var fieldFormatted = writeField(rowkey, rowdata, protected, annotationStructure);
    annotationTarget.appendChild(fieldFormatted);
  });
  //for all annotationExtraFields create a new editable field:
  for (var i = 0; i < annotationExtraFields.length; i++) {
    annotationTarget.appendChild(writeField(annotationExtraFields[i], '', false, annotationStructure));
  }
  /*
  // TODO determine if this codeblock has to go back into production. 
  //work with the Author of the annotation:
  Object.keys(authorData).forEach(key =>{
    var row = authorData[key];
    var rowkey = row[0];
    var rowdata = row[1];
    var protected = row[2];
  });*/
  //show the type of the annotation as a header entry: 
  if (datamode === 'controll') {
    let etType = createDivider('Entity: ' + data['entity'][0]['type']); 
    //var etType = document.createElement('h3');
    var etStable = data['entity'][0]['stableURI'];
    //etType.classList.add('font-bold', 'text-lg', 'w-full', 'items-center', 'flex', 'justify-center');
    //var etTypeText = document.createTextNode('Entity: ' + data['entity'][0]['type']);
    // TODO: LOW PRIORITY: styling. properties of entity needs to be shown here!! 
    let etpropdiv = document.createElement('div'); 
    etpropdiv.classList.add('blabla'); 
    //console.log(data['entity'][0]); 
    etpropdiv.appendChild(showETProps(data['entity'][0].properties, annotationStructure));
    //TODO ==> get rid of showETProps and put a call to call to writeField subfunction here

    //etType.appendChild(etTypeText);
    annotationTarget.appendChild(etType);
    annotationTarget.appendChild(etpropdiv); 
    let neoid = data['entity'][0]['neoID'];
    gateWay.appendChild(createStableLinkingBlock(neoid, etStable));
    annotationTarget.appendChild(gateWay);
    //display the variant data: 
    displayET_variant(data['variants'], neoid);
    //With the type known: look up if there's a wikidata attribute: 
    var qidArr = data['entity'][0]['properties'].filter(ar => ar[2] == 'wikidata');
    if (qidArr.length === 1) {
      var qid = qidArr[0][1];
      var wd = new wikibaseEntry(qid, wdProperties, 'slideover', 'qid');
      wd.getWikidata()
        .then(function () { wd.renderEntities(qid) });
    }
  } else if (datamode === 'automated') {
    console.log('start here: converts automatic annotation to confirmed one. ');
    // BUG  this is related to double trigger of entity dropdown menu, but not the cause!. 
    // you probably will need a way to pass datamode to the appropriate function! since datamode
    // is part of the global scope, this shouldn't be difficult!

    //find the selected text: 
    let highlighted = document.getElementsByClassName("markedAnnotation");
    let highlightedText = '';
    console.log(highlighted.innerHTML);
    for (const element of highlighted) {
      highlightedText += element.textContent;
    }
    console.log('highlight is', highlightedText);
    globalSelectionText = highlightedText;
    $baseURL = '/AJAX/getEntitySuggestion.php?';
    $parameters = {
      'type': '',    //type is empty as there was no pickup by NERtool
      'value': highlightedText,
      'casesensitive': false
    };
    $sendTo = $baseURL + jQuery.param($parameters);
    makeSuggestionBox();
    getInfoFromBackend($sendTo)
      .then((data) => {
        loadIntoSuggestionBox(data, globalSelectionStart, globalSelectionEnd);
      })
    // TODO: CRITICAL
    /**
     *        1) code needs to perform a lookup in the DOM and see what the annotated text is.                                        OK
     *        2) This annotated text should be treated as if you select a part of the DOM text and look it up in the DB.              OK
     *        3) UPON approval= 
     *            - update annotation Label from annoation_auto to annoation
     *            - add other fields in DOM to approve edit and let the user annotate properly. 
     */

    // TODO: TESTPROCEDURES you need a good test procedure to finish the JS components: 
    /**     TAKE TEXT 10004 for this:
     * 1) Create a new entity ==>
     *    - Q-id is not used yet
     *    - Does not match any known variant.
     * 2) Connect annotation to entity ==>
     *    - Via Q-id 
     *    - Without known variant
     * 3) Connect annotation to entity ==>
     *    - Via Q-id
     *    - Witch matching variant which you wish to ignore!
     * 4) Connect annotation to entity ==> 
     *    - No known Q-id:
     *    - With matching variant to use
     * 5) Connection annotation to entity ==>
     *    - Known Q-id, you wish to ignore
     *    - With matchin variant to use!
     * 
     */
  }
}

function handleError() {
  // TODO: is not actually being called at the moment!
  alert('handleError function needs to be rewritten for a more uniform layout. ');
  return;
  //frameWorkBase();
  var target = document.getElementById('annotationContainerAjax');
  target.classList.add('bg-red-100', 'rounded-lg', 'py-5', 'px-6', 'mb-3', 'text-base', 'text-red-700', 'inline-flex', 'items-center');
  var errtitle = document.createElement('h4');
  var errdiv = document.createElement('div');
  errdiv.classList.add('flex', 'items-center', 'justify-center');
  var errsvg = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /> </svg>';
  errdiv.innerHTML = errsvg;
  errtitle.classList.add('warning');
  var errmessage = document.createElement('p');
  errmessage.classList.add('warning');
  var errmessagetext = document.createTextNode('An unspecified error occurred. The ID may not be valid or has been deleted from the database.');
  errmessage.appendChild(errmessagetext);
  target.appendChild(errtitle);
  target.appendChild(errdiv);
  target.appendChild(errmessage);
}

function loadAnnotationData(annotationID = false) {
  //BUG: existing Annotation_auto ID gets retained and added after confirming a recognized ET
  if (!(annotationID)){
    //get annotationID in case of clickevent trigger: find the source of the event. 
    var eventsource = event.source || event.target;
    //event.preventDefault();
    var annotationID = eventsource.dataset.annotation;
  }
  //otherwise the annotationID is given as of the function call parameter(in case of
  //  programatically triggering the event). 
    
  getInfoFromBackend("/AJAX/resolve_annotation.php?annotation=" + annotationID)
    .then((data) => {
      if (data['code']==-1){
        handleError(); 
      }else{
        showdata(data);
      }
    })
  .catch(err => handleError() );
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

function showDBInfoFor(id, extra = '') {
  /*
  * Uses the internal NEOID identifier to fetch all information of a given entity.
  * given info includes: variants, properties, stable id, label and the datamodel!
  * THIS is a function specific to the disambiguation process coming from showhit()-calls. 
  * Needs to be extende so that it shows the actual data in the DOM. 
  */
  let extended = '';
  if (extra) {
    extended = '&extended=1'
  }
  // shows all data there's stored about it.
  getInfoFromBackend('/AJAX/getETById.php?id=' + id + extended)
    .then((data) => {
      //process entity information: 
      //  Use the order defined by the model to show properties: 
      const model = data['extra']['model']; 
      const info = data['props'];
      // Iterate over properties
      //let reduced = info.map(sublist => sublist[0]);    //OK: 
      /*for (let mod of Object.values(model)){ 
        let modName = mod[0]; 
        if(reduced.indexOf(modName)>-1){
          //let domElement = document.createElement('span');
          console.log('PROP FOUND: ', modName, info[reduced.indexOf(modName)]);
        }
      }*/

      // for (let i = 0; i < model.length; i++) {
      //   let modelBlock = model[i]; 
      //   let domName = modelBlock[0]; 
      //   let domType = modelBlock[1]; 
      // }
      // for (let i = 0; i < info.length; i++) {
      //   let infoBlock = info[i];
      //   let blockName = infoBlock[0];
      //   let blockData = infoBlock[1];
      //   console.log(infoBlock);
      // }
      //process: variants
      const variants = data['variantSpellings'];
      const uri = data['stable'];
      neoVarsToDom(variants, 1); 
      //make varbox visible!
      document.getElementById('embeddedSpellingVariants').classList.remove('hidden'); 
      //showing entity in the DOM: 
      //1:  Make empty
      let proptarget = document.getElementById('displayHitEt'); 
      proptarget.innerHTML = ''; 
      //show the type of entity that has a potential match!
      let typeOfEt = data.extra.label;
      proptarget.appendChild(createDivider('Entity: '+typeOfEt)); 
      //2:  use writeField function!
      Object.values(data['props']).forEach((prop) => {
        console.log('property: ', prop); 
        let propKey = prop[0];
        let propVal = prop[1];
        console.log(propKey, propVal); 
        let subelem = writeField(propKey, propVal, true, data.extra.model);
        console.log(subelem); 
        proptarget.appendChild(subelem); 
      });
      //TODO: option to connect ET to current selection is still missing!!

      let referenceNode = document.getElementById('relatedTextStats').parentElement;
      //remove the stable block if it exists: 
      let stableBox = document.getElementById('stableBox');
      if (stableBox) { stableBox.remove(); }
      referenceNode.appendChild(createStableLinkingBlock(id, uri));

    });

}

$(document).ready(function () {
  addInteractionToEntities();
});
