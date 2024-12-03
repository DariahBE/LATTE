let globalSelectionText = null;
let globalSelectionStart = null;
let globalSelectionEnd = null;
let targetOfInfo = null; 


function insertAfter(referenceID, elementToAdd){
  /**
   *  counterpart to  native insertBefore()
   *  takes two arguments: 
   *    1) The ID of a reference element
   *    2) The element to insert
   *  if the reference element is NULL it will give a console warning!
   */
  const referenceNode = document.getElementById(referenceID);
  if (referenceNode === null){
    console.error('Element with ID '+referenceID+' was not found in the DOM' ); 
    return; 
  }
  referenceNode.parentNode.insertBefore(elementToAdd, referenceNode.nextSibling);
}
function ignoreSuggestion(box = "suggestionOnSelect") {
  var isOpen = document.getElementById(box);
  if (isOpen) {
    isOpen.remove();
  }
  toggleSlide(0);
}


function handleNoLogin(){
  // RUN MOD ON: #embeddedET 
  //call whenever nonlogin is detected from calls to get_structure.php

  /*modifies the DOM to hide elements that require login
  NOTE: this is not a security feature. Data that requires
  sessions are protected serverside. This is clientside code
  that simply prevents making DOM-elements to query/ put data
  */

  let target = document.getElementById('embeddedET'); 
  target.innerHTML = ''; 
  let warningDiv = document.createElement('div'); 
  warningDiv.classList.add('notice', 'w-full'); 
  let warningParagraph = document.creatElement(p)
  let warningText = document.createTextNode('This feature is disabled for non-registered users. For the current account policy, refer to ');
  let warningLinkText = document.createTextNode('this page');
  let warningLink = document.creatElement('a'); 
  warningLink.setAttribute('href', '/register.php'); 
  warningLink.setAttribute('target', '_blank'); 
  warningLink.appendChild(warningLinkText); 
  warningParagraph.appendChild(warningText); 
  warningParagraph.appendchild(warningLink); 
  target.appendchild(warningParagraph); 
}

function extractAnnotationPropertiesFromDOM(domBlock) {
    // not confirmed in second call!
  let prop = {}
  // console.log('extracting DOM properties'); 
  for (let i = 0; i < domBlock.length; i++) {
    let box = domBlock[i].getElementsByClassName('inputelement')[0];
    // console.log(box);
    let boxName = box.name;
    let boxValue = extractValueType(box);
    // console.log(boxName, boxValue);
    prop[boxName] = boxValue;
  }
  return prop;
}


function typeToHtml(type, defaultValue = 'text') {
  //converts configured type to valid html types:
  const conversionList = {
    //'longtext': false,
    'wikidata': 'text',
    'string': 'text',
    'int': 'number',
    'bool': 'checkbox',
    'uri': 'url'
  }
  // Check if the type exists in the conversionList, if yes, return the corresponding HTML type
  if (conversionList.hasOwnProperty(type)) {
    return conversionList[type];
  }
  // If type doesn't exist, return the default value
  return defaultValue;
}
function typeToPattern(type, defaultValue = false) {
  const conversionList = {

  }
  // Check if the type exists in the conversionList, if yes, return the corresponding HTML type
  if (conversionList.hasOwnProperty(type)) {
    return conversionList[type];
  }

  // If type doesn't exist, return the default value
  return defaultValue;
}

function extractValueType(htmlElem) {
  //takes an input box, then uses the correct method to get the value from each htmlElem
  if (htmlElem.tagName === 'INPUT') {
    if (htmlElem.type === 'checkbox') {
      return htmlElem.checked;
    } else {
      return htmlElem.value;
    }
  } else if (htmlElem.tagName === 'TEXTAREA') {
    return htmlElem.value;
  }
}

function displayUpdatedText(type, start, stop, uuid){
  let text = document.querySelectorAll('.ltr');
  //filter text with start/stop values: 
  Array.from(text).forEach((e) => {
    const iterCounter = parseInt(e.dataset.itercounter);
    if (iterCounter >= start && iterCounter <= stop) {
      //newly created ANNOs have to be clickable, marked and receive their UUID!
      e.classList.add(type, 'linked', 'underline');
      e.classList.remove('app_automatic');
      e.dataset.annotation = uuid;
      e.addEventListener('click', function(){
        loadAnnotationData(); 
      }); 
    }
  });
}

function saveNewDB() {
  /**
   * Function that saves a newly created entity in the Database.
   * Triggered when a text selection is not recognized as an existing ET, 
   * will create a new ET, ANNO and variant node.
   */
  let mistakes = document.getElementsByClassName('validatorFlaggedMistake');
  //IS erverything valid 
  //check if required fields are set: 
  validator.checkRequired(); 
  //validate in backend too!!
  let dataObject = {};
  if (mistakes.length == 0) {
    //get a CSRF token
    fetch("/AJAX/getdisposabletoken.php?task=1")
      .then(response => response.json())
      .then(data => {
        const token = data;     ///CSRF token
        //fetch all fields: 
        ///////ANNOTATION: 
        //get text that is selected
        const selectedText = globalSelectionText;
        //get start position of annotation: 
        const startOfSelection = globalSelectionStart;
        //get end position of annotation: 
        const endOfSelection = globalSelectionEnd;
        /////// VARIANTS: 
        let variantSpellings = document.getElementById('variantStorageBox').getElementsByClassName('writtenvariantvalue');
        let foundVariants = [selectedText];
        for (p = 0; p < variantSpellings.length; p++) {
          foundVariants.push(variantSpellings[p].textContent);
        }
        /////// PROPERTIES of a given entity: 
        let properties = {};
        const etType = document.getElementById('entityTypeSelector').value;
        let propertyPairs = document.getElementById('propertyBox').getElementsByTagName('div');
        for (let pp = 0; pp < propertyPairs.length; pp++) {
          let pair = propertyPairs[pp].getElementsByTagName('input')[0];
          //BUGpatch for misscasting off bools to always true! (OK)
          let pairValue; 
          if (pair.type === 'checkbox'){
            pairValue = pair.checked;
          }else{
            pairValue = pair.value;
          }
          let pairName = pair.name;
          properties[pairName] = pairValue;
        }

        //appending to dataObject
        dataObject['token'] = token;
        dataObject['texid'] = languageOptions['nodeid'];
        dataObject['nodetype'] = etType;
        //let annotationCollectionBox = {};
        let annotationProperties = document.getElementById('annotationCreationDiv').getElementsByClassName('property');
        let annotationCollectionBox = extractAnnotationPropertiesFromDOM(annotationProperties);
        annotationCollectionBox[startcode] = startOfSelection;
        annotationCollectionBox[stopcode] = endOfSelection;

        dataObject['annotation'] = annotationCollectionBox;
        dataObject['variants'] = foundVariants;
        dataObject['properties'] = properties;    // == entity
        //    datamode = controll OR automated
        dataObject['annotationmode'] = datamode;
        //if the datamode indicates it's an automated node: you need to pass the node UID so it can be updated. 
        //Annotation_auto nodes always have a UID, so that's a feasable solution.
        dataObject['neo_id_internal'] = auto_annotation_internal_id; 
        //send dataobject to backend: 
        // backend needs to know if this is an update or insert operation! Annotation or Annotation_auto node
        $.post("/AJAX/put_annotation.php", { data: dataObject })
          .then(function( data ) {
            let put_rs = data['data']; 
            loadAnnotationData(put_rs['uuid']);
            displayUpdatedText(put_rs['type'], put_rs['start'], put_rs['stop'], put_rs['uuid']); 
          })
          .catch(function(data){
            let errorMessage = data['ERR']; 
            updateState('ERROR', errorMessage); 
          })
          .always(function(){
            auto_annotation_internal_id = NaN; 
          })
      }); 
      document.getElementById('saveEtToDb').setAttribute('disabled', true); // Prevents dual submission!
  } else {
    //OK
    //  ==> form data is not valid: types don't match config definitions.
    let shakeButton = document.getElementById('saveEtToDb');
    shakeButton.classList.add('animate-shake');
    shakeButton.addEventListener("animationend", function () {
      shakeButton.classList.remove('animate-shake');
    }, false);
  }
  //show request results.
}

function toggleSlide(dir = 0) {
  chosenQID = null;
  // 0 closes the sidepanel; 1 opens it. Better than the original .toggle() functions
  if (dir === 0) {
    unmark(); //when closing the side panel, always remove the markup!
    document.getElementById('slideover-container').classList.add('invisible');
    document.getElementById('slideover').classList.add('translate-x-full');
  } else {
    document.getElementById('slideover-container').classList.remove('invisible');
    document.getElementById('slideover').classList.remove('translate-x-full');
  }
}

function loadPropertiesOfSelectedType(selected) {
  //reads from the DOM which entity is being created; 
  //does an AJAX call to fetch the structure of the entity and matches with config file
  //fields get generated and appended. If old fields exist, they are removed. 
  let selector = document.getElementById('entityTypeSelector');
  if (!(selected)){
    selected = selector.value; //dropdown value selected. 
  }
  //if a user made a mistake: remove the old formcontent
  let deleteOldBox = document.getElementsByClassName('generatedFieldsForFormBox');
  while (deleteOldBox.length > 0) {
    deleteOldBox[0].parentNode.removeChild(deleteOldBox[0]);
  }
  //load properties for selected: 
  //you need to insert the form data after the selector element!
  //??create a dual display: one with the option to add a new entity, one with the option to attach the annotation to an existing annotation. 
  let formBox = document.createElement('div');
  formBox.setAttribute('id', 'propertyBox');
  formBox.classList.add('w-full', 'generatedFieldsForFormBox');
  let propertyPrompt = document.createElement('p');
  propertyPrompt.appendChild(document.createTextNode('2) Properties:'));
  propertyPrompt.classList.add('text-lg', 'p-2', 'm-2');
  formBox.appendChild(propertyPrompt);

  //let formBoxHeader = document.createElement('p');
  //formBoxHeader.appendChild(document.createTextNode(selected+' info:'));
  fetch('/AJAX/get_structure.php?type=' + selected)
    .then((response) => response.json())
    .then((data) => {
      if (data['msg'] == 'success') {
        var nodedata = data['data'];
        Object.entries(nodedata).forEach(entry => {
          const [key, value] = entry;
          console.log(key, value);
          var humanLabel = value[0];
          var datatype = value[1];
          let uniqueness = value[2]; 
          let newFieldContainer = document.createElement('div');
          let newFieldLabel = document.createElement('label');
          let newFieldInput;
          // if (datatype === 'longtext') {
          //   //longtext not used any longer. 
          //   newFieldInput = document.createElement('textarea');
          // } else {
          //   newFieldInput = document.createElement('input');
          // }
          newFieldInput = document.createElement('input');
          newFieldInput.classList.add('inputelement');
          newFieldLabel.appendChild(document.createTextNode(humanLabel + ': '));
          if (datatype === 'wikidata' && chosenQID !== null) {
            newFieldInput.value = chosenQID;
            newFieldInput.disabled = true;
          }
          newFieldLabel.setAttribute('for', key);
          newFieldInput.setAttribute('name', key);
          newFieldInput.setAttribute('data-name', key);
          let htmlType = typeToHtml(datatype);
          if (htmlType !== false) {
            newFieldInput.setAttribute('type', htmlType);
          }
          let expectedPattern = typeToPattern(datatype);
          if (expectedPattern) {
            newFieldInput.setAttribute('pattern', expectedPattern);
          }
          if(uniqueness){
            //test passed: DOM contains class!
            newFieldInput.classList.add('validateAs_unique');
            newFieldInput.required = true; 
          }
          newFieldInput.classList.add('attachValidator');
          newFieldInput.classList.add('validateAs_' + datatype);
          newFieldInput.classList.add('border', 'border-gray-300', 'text-gray-900', 'rounded-lg', 'p-2.5', 'my-1');
          newFieldContainer.appendChild(newFieldLabel);
          newFieldContainer.appendChild(newFieldInput);
          formBox.appendChild(newFieldContainer);
        });
        //formBox.appendChild(formBoxHeader);
        selector.parentElement.appendChild(formBox);
        //attach validator: 
        validator = new Validator;
        validator.pickup();
        //make a save button to commit the data: 
        let saveNewEntry = document.createElement('button');
        saveNewEntry.setAttribute('id', 'saveEtToDb')
        saveNewEntry.classList.add('bg-green-400', 'mx-2', 'px-2', 'my-1', 'py-1', 'rounded');
        saveNewEntry.appendChild(document.createTextNode('Save'));
        saveNewEntry.addEventListener('click', function () {
          saveNewDB();
        });
        formBox.appendChild(saveNewEntry);
      }else if (data['msg'] == 'failed'){
        if(data['datacode'] = 0 ){
          handleNoLogin()
        }
      }
    })
}

function generateHyperlink(anchor, href, classlist = [], id = false, anchormode = 'text', newtab = true) {
  var a = document.createElement('a');
  a.setAttribute('href', href);
  if (newtab) {
    a.setAttribute('target', '_blank');
  }
  for (let i = 0; i < classlist.length; i++) {
    a.classList.add(classlist[i]);
  }
  if (anchormode == 'text') {
    var t = document.createTextNode(anchor);
  } else if (anchormode == 'image') {
    var t = document.createElement('img');
    t.setAttribute('src', anchor);
  }
  a.appendChild(t);
  if (id) {
    a.setAttribute('id', id);
  }
  return a;
}

function buildPropertyInputFieldsFor(label) {
  return new Promise((resolve, reject) => {
    let fieldContents = [];
    fetch('/AJAX/get_structure.php?type=' + label)
      .then((response) => response.json())
      .then((data) => {
        if (data['msg'] == 'success') {
          var nodedata = data['data'];
          Object.entries(nodedata).forEach(entry => {
            const [key, value] = entry;
            var humanLabel = value[0];
            var datatype = value[1];
            let uniqueness = value[2]; 
            let newFieldContainer = document.createElement('div');
            newFieldContainer.classList.add('property'); 
            let newFieldLabel = document.createElement('label');
            let newFieldInput;
            newFieldInput = document.createElement('input');
            //needs the override attribute set to the input element when annotationform is being made. 
            if(label === annocoreNode){
              newFieldInput.setAttribute('data-nodetype_override', label);
            }
            newFieldInput.classList.add('inputelement');
            newFieldLabel.appendChild(document.createTextNode(humanLabel + ': '));
            if (datatype === 'wikidata' && chosenQID !== null) {
              newFieldInput.value = chosenQID;
              newFieldInput.disabled = true;
            }
            //let nameVar = key; 
            newFieldLabel.setAttribute('for', key);
            newFieldInput.setAttribute('name', key);
            newFieldInput.setAttribute('data-name', key);
            let htmlType = typeToHtml(datatype);
            if (htmlType !== false) {
              newFieldInput.setAttribute('type', htmlType);
            }
            let expectedPattern = typeToPattern(datatype);
            if (expectedPattern) {
              newFieldInput.setAttribute('pattern', expectedPattern);
            }
            if(uniqueness){
              newFieldInput.classList.add('validateAs_unique');
              newFieldInput.required = true; 
            }
            newFieldInput.classList.add('attachValidator');
            newFieldInput.classList.add('validateAs_' + datatype);
            newFieldInput.classList.add('border', 'border-gray-300', 'text-gray-900', 'rounded-lg', 'p-2.5', 'my-1');
            newFieldContainer.appendChild(newFieldLabel);
            newFieldContainer.appendChild(newFieldInput);
            fieldContents.push(newFieldContainer);
          });
        }else if (data['msg'] == 'failed'){
          if(data['datacode'] = 0 ){
            handleNoLogin()
          }
        }
        resolve(fieldContents);
      })
  })
}

async function connectAnnoToEntity(neoid_et, text_neo_id, selection_start, selection_end, selected_text, extra_properties, token){
  await new Promise((resolve) => {
    let postData = {
      sourceNeoID: neoid_et,
      texNeoid: text_neo_id,
      csrf: token,
      start: selection_start,
      stop: selection_end,
      selection: selected_text, 
      properties: extra_properties
    };
    $.ajax({
      type: "POST",
      url: "/AJAX/crud/connect.php",
      data: postData,
      dataType: "json",
      success: function (repldata) {
        let repl = document.createElement('p');
        repl.appendChild(document.createTextNode(repldata['msg']));
        document.getElementById('etmain').appendChild(repl);
        let annotationStart = repldata['start'];
        let annotationEnd = repldata['stop'];
        let annotationUID = repldata['annotation'];
        let annotationForType = repldata['type'];
        loadAnnotationData(annotationUID);
        displayUpdatedText(annotationForType, annotationStart, annotationEnd, annotationUID); 
        resolve(); //Resolve the Promise when the fetch operation completes
      }
    }).always(
      function () {
        document.getElementById('assignEtToSelectionParent').remove(); //delete annotation button
      }
    )
  })



}

function startEntityCreationFromScratch(){
  //update state: make it clear that the user instantiated this: 
  updateState('State', 'A match was rejected, you can now create a new annotation and entity.'); 
  acceptQID(-1);
  //clear the current entity: 
  document.getElementById('etmain').innerHTML = ''; 
  deleteIfExistsById('WDResponseTarget');
  deleteIfExistsById('etcreate');
  //remove current link to wikidata id and remove wd object from global scope!: 
  wikidataID = -1;
  wd = null; 
  buildAnnotationCreationBox();
}

function deleteIfExistsById(id){
  let elem = document.getElementById(id); 
  if (elem !== null){
    elem.remove(); 
  }
}

let wikidataID; 
function showET(etdata, levscore = false, weightscore = false, variants = [], show_wikidata_disambiguation_panel = true) {
  //alert('labeltest required from all callers!')
  /**
   *      function will display WD, label and properties for any 
   *  given entity that has en entry in the database. If a link
   *  is accepted, the given neoid of the entity node will be 
   *  used to generate a new annotation.
   *    - CALLED BY: 
   *  1) (OK)When the database holds a single string that matches the selection (datadictionary contains 1 item) (call comes from triggerSidePanelAction() with the first loaded node)
   *  2) (OK)When a string matches 2 or more existing annotations in the database (datadictionary contains more than 1 item)  (call comes from triggerSidePanelAction()>navET)
   *  3) (OK) showHit bugpatch confirmed!
   */
  //WARNING: variants are all labelvariants within a specific levenshtein range. 
  //    you need to filter on labels that are related to the entity ID (etdataNeoId)!
  //read the properties from the entity passed as a single argument (etdata) 
  let etdataNeoId = etdata[0];
  let etLabel = etdata[1]; 
  let properties = etdata[2];
  wikidataID = etdata[3];
  //Show the node label: 
  let close_variants = document.createElement('ul');
  let seen_values = new Set(); 
  if(variants){
    variants.forEach(element => {
      if (element[2]['variantOfEntity'][0] == etdataNeoId){
        let new_value = element[2]['variant']['value'];
        if (!seen_values.has(new_value)) {
          seen_values.add(new_value); 
          var variant_spelling = document.createElement('li');
          variant_spelling.appendChild(document.createTextNode(new_value));
          close_variants.appendChild(variant_spelling);
        }
      }
    });
  }
    // let etLabelElem = document.createElement('h2'); 
  // etLabelElem.appendChild(document.createTextNode(etLabel)); 
  // etLabelElem.classList.add('text-lgss', 'font-bold');  
  //remove old elements by their ID.
  deleteIfExistsById('lev_weight_box');
  let levbox = document.createElement('div'); 
  levbox.setAttribute('id', 'lev_weight_box'); 
  //levenshtein key + score elmement. 
  
  let levdist = document.createElement('p'); 
  if (levscore !== false){
    //key for indicator
    let levdist1 = document.createElement('span'); 
    levdist1.appendChild(document.createTextNode('Levenshtein distance: '))
    levdist1.classList.add('font-bold'); 
    //score indicator
    let levdist2 = document.createElement('span'); 
    levdist2.appendChild(document.createTextNode(levscore));
    levdist.appendChild(levdist1); 
    levdist.appendChild(levdist2); 
  }
  //weight key + score element. 
  
  let weight = document.createElement('p'); 
  if(weightscore !== false){
    //key for indicator
    let weight1 = document.createElement('span');
    weight1.appendChild(document.createTextNode('Node weight: '))
    weight1.classList.add('font-bold');
    //score indicator
    let weight2 = document.createElement('span');
    weight2.appendChild(document.createTextNode(weightscore));
    weight.appendChild(weight1);
    weight.appendChild(weight2);
  }
  
  levbox.appendChild(levdist);
  levbox.appendChild(weight);
  deleteIfExistsById('assignEtToSelectionParent');
  //deleteIfExistsById('annotationCreationDiv');
  let wd = null;
  let wdboxToDrop = document.getElementById('WDResponseTarget');
  if (wdboxToDrop) { wdboxToDrop.remove(); }
  let subtarget = document.getElementById('entitycontent');
  if (subtarget === null){
    subtarget = createMainBox(); 
    insertAfter('neobox', subtarget); 
    //let referenceNode = document.getElementById('neobox'); 
    //referenceNode.parentNode.insertBefore(subtarget, referenceNode.nextSibling);
  }
  subtarget.innerHTML = '';
  subtarget.appendChild(levbox);
  if (variants.length > 0){
    let variants_text_only = document.createElement('div');
    let variants_text_only_header = document.createElement('p'); 
    variants_text_only_header.classList.add('font-bold', 'text-lg');
    variants_text_only_header.appendChild(document.createTextNode('Spelling variants: '))
    variants_text_only.appendChild(variants_text_only_header);
    variants_text_only.appendChild(close_variants)
    subtarget.appendChild(variants_text_only);
  }
  let labelElement = document.createElement('h3'); 
  labelElement.appendChild(document.createTextNode('Entity: '+ etLabel)); 
  labelElement.classList.add('font-bold', 'text-lg', 'w-full', 'items-center', 'flex', 'justify-center'); 
  // subtarget.appendChild(etLabelElem); 
  var propdiv = document.createElement('div');
  for (let k in properties) {
    let show = null;
    //let key = k;    //Deleted, not required in for scope!
    let value = properties[k];
    let valueType = value['vartype'];
    let valueDOM = value['DOMString'];
    let datavalue = value['value'];
    if (valueType == 'uri') {
      show = generateHyperlink(valueDOM, datavalue, ['externalURILogo']);
    } else if (valueType == 'wikidata' && datavalue !== null) {
      show = document.createElement('p');
      var wdprefix = document.createElement('span');
      wdprefix.appendChild(document.createTextNode(valueDOM + ': '));
      wdprefix.classList.add('font-bold');
      let extrashow = generateHyperlink(datavalue, 'https://wikidata.org/wiki/' + datavalue, ['externalURILogo']);
      show.appendChild(wdprefix);
      show.appendChild(extrashow);
    } else {
      if (datavalue === null) { datavalue = ''; }
      show = document.createElement('p');
      let labelShow = document.createElement('span');
      let valueShow = document.createElement('span');
      labelShow.classList.add('font-bold');
      let labelShowTex = document.createTextNode(valueDOM + ': ');
      let valueShowTex = document.createTextNode(datavalue);
      labelShow.appendChild(labelShowTex);
      valueShow.appendChild(valueShowTex);
      show.appendChild(labelShow);
      show.appendChild(valueShow);
    }
    propdiv.appendChild(show);
  }
  let entityContentElement = document.getElementById('entitycontent'); 
  if(entityContentElement === null){
    let created_etnav = document.createElement('div');
    created_etnav.setAttribute('id', 'etnav'); 
    entityContentElement = document.createElement('div'); 
    entityContentElement.setAttribute('id', 'entitycontent'); 
    //showHit(2908) from the console triggers a fatal error because etmain is missing from the DOM
    //does not cause any issue when triggered using the UI - not considered to be an issue. 
    document.getElementById('etmain').appendChild(created_etnav);
    document.getElementById('etmain').appendChild(entityContentElement);
  }

  entityContentElement.appendChild(labelElement);
  entityContentElement.appendChild(propdiv);
  if (wikidataID) {
    wd = new wikibaseEntry(wikidataID, wdProperties, 'slideover', 'qid');
    wd.getWikidata()
      .then(function(){wd.renderEntities(wikidataID)});
  }
  //with the data displayed: allow the user to accept the suggestion => this creates a new annotation between
  //the text and existing ET. 
  var d = document.getElementById('assignEtToSelection');
  if (d !== null) { d.remove(); }
  fetch('/user/AJAX/profilestate.php?fastconnect=1')
    .then((response) => response.json())
    .then((data) => {
      //console.log('profilestate', data);
      if (data['valid']) {
        var csrf = data['csrf'];
        let acceptLink = document.createElement('button');
        let rejectLink = document.createElement('button'); 
        acceptLink.setAttribute('id', 'assignEtToSelection'); 
        rejectLink.setAttribute('id', 'assignNewEtToSelection'); 
        //show the user what is going on and explain why it is in this mode: 
        updateState('State', 'An entity with matching spelling was found. You can link this attestation to this entity or reject the link and create a new entity with the same spelling.'); 
        let rejectText = document.createTextNode('Reject link'); 
        let acceptText = document.createTextNode('Create annotation');
        acceptLink.appendChild(acceptText);
        rejectLink.appendChild(rejectText); 
        acceptLink.classList.add('bg-green-400');
        rejectLink.classList.add('bg-orange-400'); 
        function disableInternalButtons(){
          acceptLink.disabled = true;
          rejectLink.disabled = true;
        }

        rejectLink.addEventListener('click', function (){
          //console.log(1);
          disableInternalButtons();
          //call a function which will abort any link and starts the entity linking process from a string
          //without trying to match it to an existing ET: 
          startEntityCreationFromScratch(); 
        })
        acceptLink.addEventListener('click', async function () {
          //check if required fields are set: 
          validator.checkRequired(); 
          let mistakes = document.getElementsByClassName('validatorFlaggedMistake');
          //IS erverything valid 
          if (mistakes.length > 0 ){
            updateState('submission rejected', 'Please fix all mistakes before sumitting the form again.'); 
          } else{
            //make buttons unresponsive: 
            disableInternalButtons();

            //data to send to server
            //read the content of the div that holds annotation data when connecting nodes  
            let annotationProperties = document.getElementById('annotationCreationDiv').getElementsByClassName('property');
            let annotationCollectionBox = extractAnnotationPropertiesFromDOM(annotationProperties);
            await connectAnnoToEntity(etdataNeoId, languageOptions['nodeid'], globalSelectionStart, globalSelectionEnd, globalSelectionText, annotationCollectionBox,  csrf); 
          }
        });
        //calls a helper function that generates the input elements
        //according to their type. All elements are then added to
        //start with creating the annotation box: use a single function for this
        //which is responsible for the annobox throughout the entire code!
        buildAnnotationCreationBox(show_wikidata_disambiguation_panel); 
        //swap DOM layout
        var embeddedETRef = document.getElementById('embeddedET');
        if (embeddedETRef) {
          embeddedETRef.classList.remove('hidden');
        }
        var annotationCreationDivRef = document.getElementById('annotationCreationDiv');
        if (annotationCreationDivRef) {
          annotationCreationDivRef.classList.remove('hidden');
        }
        var etselectdivRef = document.getElementById('etselectdiv');
        var nodeTypeSelectionRef = document.getElementById('nodeTypeSelection');
        if (etselectdivRef) {
          etselectdivRef.classList.add('hidden');
        }
        if (nodeTypeSelectionRef) {
          nodeTypeSelectionRef.classList.add('hidden');
        }        
        //make a save button to commit the data: 
        let saveNewEntry = document.createElement('button');
        saveNewEntry.setAttribute('id', 'saveEtToDb');
        saveNewEntry.classList.add('bg-green-400', 'mx-2', 'px-2', 'my-1', 'py-1', 'rounded');
        saveNewEntry.appendChild(document.createTextNode('Save'));
        saveNewEntry.addEventListener('click', function () {
          saveNewDB();
        });
        let annoToEtConnectorParent = document.createElement('div'); 
        annoToEtConnectorParent.setAttribute('id', 'assignEtToSelectionParent'); 
        annoToEtConnectorParent.classList.add('w-full', 'm-1', 'p-1', 'flex'); 
        acceptLink.classList.add('flex-1', 'm-1'); 
        rejectLink.classList.add('flex-1', 'm-1'); 
        annoToEtConnectorParent.appendChild(acceptLink); 
        annoToEtConnectorParent.appendChild(rejectLink); 
        document.getElementById('etmain').appendChild(annoToEtConnectorParent);
      }
    })
}

function updateState(key, msg){
  /**
   * To show the user why the program goes into a specific mode; explain what's going on!
   */
  document.getElementById('usernoticekey').textContent = key+': ';
  document.getElementById('usernoticevalue').textContent = msg;
}

function createEmbbeddedETDiv(){
  //alert('called??'); 
  var embeddedCreateDiv = document.createElement('div');
  embeddedCreateDiv.setAttribute('id', 'embeddedET');
  embeddedCreateDiv.classList.add('hidden');
  return embeddedCreateDiv; 
}

function createWDPromptBox(createNodeDiv, positionDiv){
  //creates the wikidata prompting box where string based matching is done. 
  document.getElementById('embeddedSpellingVariants').classList.add('hidden');
  //let spellingVariantDOMReturn = displayET_variant(null, null);
  //variantbox has to be invisible in this phase: entity still needs to be created!!
  //wikidataPrompt: 
  var wikidataQLabel = document.createElement('div');
  wikidataQLabel.setAttribute('readonly', true);
  wikidataQLabel.setAttribute('id', 'chosenQID');
  var wikidataPromptMainbox = document.createElement('div');
  wikidataPromptMainbox.setAttribute('id', 'wdsearchpromptbox');
  wikidataPromptMainbox.classList.add('my-2', 'py-2', 'border-solid', 'border-2', 'border-black-800', 'rounded-md', 'flex-grow');
  let wikidataPromptExplain = document.createElement('p');
  wikidataPromptExplain.classList.add('text-sm', 'w-full', 'text-center');
  wikidataPromptExplain.appendChild(document.createTextNode('Wikidata lookup using this keyword: '));
  wikidataPromptMainbox.appendChild(wikidataPromptExplain);
  let wikidataLogoBox = document.createElement('img');
  wikidataLogoBox.setAttribute('src', '/images/wikidatawiki_small.png');
  wikidataLogoBox.classList.add('h-auto', 'max-h-10', 'rounded-r-lg', 'p-1');
  let wikidataRowBox = document.createElement('div');
  wikidataRowBox.classList.add('flex');
  wikidataRowBox.appendChild(wikidataLogoBox);
  var wikidataInputBox = document.createElement('input');
  wikidataInputBox.setAttribute('id', 'wikidataInputPrompter');
  wikidataInputBox.addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
      wdprompt(wikidataInputBox.value, 0);
  }
  })
  //console.log('creating wdibox.'); 
  wikidataInputBox.classList.add('border', 'border-gray-300', 'rounded-md', 'shadow-sm', 'focus:outline-none', 'focus:border-indigo-500');
  wikidataInputBox.value = globalSelectionText;
  wikidataRowBox.appendChild(wikidataInputBox);
  //You need to make it possible for users to create an entity in the database that have no existing wikidata ID: 
  var noWikidataId = document.createElement('button'); 
  var noWikidataIdText = document.createTextNode('Don\'t link'); 
  noWikidataId.appendChild(noWikidataIdText);
  noWikidataId.classList.add('bg-orange-500', 'border-solid', 'hover:bg-orange-600', 'p-2', 'm-2', 'rounded-lg', 'text-white', 'font-bold');
  if(globalLoginAvailable){
    noWikidataId.addEventListener('click', function () {
      //IF you pass -1 the application won't store the QID. Any newly created entity won't have a value set in the wikidata field. 
      qid = -1; 
      wd = null; 
      acceptQID(-1);
    }); 
  }
  var searchButtonForWDPrompt = document.createElement('button');
  var searchButtonForWDPromptText = document.createTextNode('Search');
  searchButtonForWDPrompt.classList.add('bg-green-500', 'border-solid', 'hover:bg-green-600', 'p-2', 'm-2', 'rounded-lg', 'text-white', 'font-bold');
  searchButtonForWDPrompt.appendChild(searchButtonForWDPromptText);
  searchButtonForWDPrompt.addEventListener('click', function () {
    wdprompt(wikidataInputBox.value, 0);
  });
  var wikidataResultsBox = document.createElement('div');
  wikidataResultsBox.setAttribute('id', 'wdpromptBox');
  wikidataPromptMainbox.appendChild(wikidataQLabel);
  wikidataPromptMainbox.appendChild(wikidataRowBox);
  wikidataPromptMainbox.appendChild(searchButtonForWDPrompt);
  if(globalLoginAvailable){
    wikidataPromptMainbox.appendChild(noWikidataId);
  }
  wikidataPromptMainbox.appendChild(wikidataResultsBox);

  //add all boxes to the DOM: 
  //      PositionDiv is special, put it after annotationCreationDiv if it exists
  //      otherwise stick to default behaviour. 
  let referenceElement = document.getElementById('annotationCreationDiv'); 
  if(!(referenceElement !== null)) {
    insertAfter('annotationCreationDiv', subtarget); 
  } else {
    if (positionDiv !== false){
      createNodeDiv.appendChild(positionDiv);
    }
  }
  //createNodeDiv.appendChild(spellingVariantMainBox);
  createNodeDiv.appendChild(spellingVariantDOMReturn.get_HTML_content());
  //add a WD Promptbox and trigger the function for wikidata_prompting from here:
  createNodeDiv.appendChild(wikidataPromptMainbox);
  searchButtonForWDPrompt.click();
  //done with spelling variants: 
  return createNodeDiv; 
}


function buildAnnotationCreationBox(include_promptbox = true) {
  /**
   *  INCOMMING CALLS:
   * 1) 
   * 2) 
   * 3) 
   * 4) 
   * 
   */
  if (document.getElementById('etcreate') !== null){
    return; 
  }
  var createNodeDiv = document.createElement('div');
  createNodeDiv.classList.add('w-full');
  createNodeDiv.setAttribute('id', 'etcreate');
  /*
  var embeddedCreateDiv = document.createElement('div');
  embeddedCreateDiv.setAttribute('id', 'embeddedET');
  embeddedCreateDiv.classList.add('hidden');
  */
  var embeddedCreateDiv = createEmbbeddedETDiv(); 
  var annotationDiv = document.createElement('div');
  annotationDiv.classList.add('hidden');
  annotationDiv.setAttribute('id', 'annotationCreationDiv');
  var topTex = document.createElement('h3');
  topTex.classList.add('uppercase', 'text-xl', 'underline', 'decoration-4', 'underline-offset-2');
  topTex.appendChild(document.createTextNode('Create a new annotation'));
  //get annotation structure: 
  let topBox = document.createElement('div');
  fetch('/AJAX/get_structure.php?type=createNewAnnotation')
    .then((response) => response.json())
    .then((data) => {
      if (data['msg'] == 'success') {
        //exclude: start, stop and selectedtext info!
        Object.entries(data['data']).forEach(entry => {
          const [key, value] = entry;
          var humanLabel = value[0];
          var datatype = value[1];
          let uniqueness = value[2]; 
          if (data['exclude'] && data['exclude'].includes(key)) {
            //do not use the key:
          } else {
            let newFieldContainer = document.createElement('div');
            newFieldContainer.classList.add('property');
            let newFieldLabel = document.createElement('label');
            let newFieldInput;
            // if (datatype === 'longtext') {  
            //   //longtext not used any longer. 
            //   newFieldInput = document.createElement('textarea');
            // } else {
            //   newFieldInput = document.createElement('input');
            // }
            newFieldInput = document.createElement('input');
            newFieldInput.classList.add('inputelement');
            newFieldLabel.appendChild(document.createTextNode(humanLabel + ': '));
            newFieldLabel.setAttribute('for', key);
            newFieldInput.setAttribute('name', key);    //patch for 9-7-2024 BUG
            newFieldInput.setAttribute('data-name', key);
            newFieldInput.setAttribute('data-nodetype_override', annocoreNode);
            let htmlType = typeToHtml(datatype);
            if (htmlType !== false) {
              newFieldInput.setAttribute('type', htmlType);
            }
            newFieldInput.setAttribute('type', htmlType);
            let expectedPattern = typeToPattern(datatype);
            if (expectedPattern) {
              newFieldInput.setAttribute('pattern', expectedPattern);
            }
            if(uniqueness){
              newFieldInput.classList.add('validateAs_unique');
              newFieldInput.required = true;  
            }
            newFieldInput.classList.add('attachValidator');
            newFieldInput.classList.add('validateAs_' + datatype);
            newFieldInput.classList.add('border', 'border-gray-300', 'text-gray-900', 'rounded-lg', 'p-2.5', 'my-1');
            newFieldContainer.appendChild(newFieldLabel);
            newFieldContainer.appendChild(newFieldInput);
            topBox.appendChild(newFieldContainer);
          }
        });
    }else if (data['msg'] == 'failed'){
      if(data['datacode'] = 0 ){
        handleNoLogin()
      }
    }
    })
    .then(()=> {
      validator = new Validator; 
      validator.pickup(); 
    });


  annotationDiv.appendChild(topTex);
  annotationDiv.appendChild(topBox);
  //      customizable annoation information: 
  embeddedCreateDiv.appendChild(annotationDiv);
  //add code to create a node from selection!
  //append newly created Div to the DOM: 
  targetOfInfo.appendChild(createNodeDiv);
  //dropdown: select the entity type ==> use the color dict available.
  var entityTypeDiv = document.createElement('div');
  entityTypeDiv.setAttribute('id', 'etselectdiv');
  var entityTypePrompt = document.createElement('p');
  entityTypePrompt.classList.add('text-lg', 'p-2', 'm-2');
  //console.warn('Related to BUG10: race condition.');
  entityTypePrompt.appendChild(document.createTextNode('1) Set entity type: '));
  entityTypeDiv.appendChild(entityTypePrompt);
  //alert('YES');
  var entityTypeSelectorDiv = document.createElement('div'); 
  entityTypeSelectorDiv.setAttribute('id', 'nodeTypeSelection'); 
  var setEntityType = document.createElement('select');
  setEntityType.setAttribute('id', 'entityTypeSelector');
  var entityTypeOptionPrompt = document.createElement('option');
  entityTypeOptionPrompt.appendChild(document.createTextNode('Entities: '));
  entityTypeOptionPrompt.setAttribute('selected', true);
  entityTypeOptionPrompt.setAttribute('disabled', true);
  setEntityType.appendChild(entityTypeOptionPrompt);
  for (var c = 0; c < coreNodes.length; c++) {
    var o = document.createElement('option');
    o.appendChild(document.createTextNode(coreNodes[c]));
    o.setAttribute('value', coreNodes[c]);
    setEntityType.appendChild(o);
  }
  embeddedCreateDiv.appendChild(entityTypeDiv);
  entityTypeSelectorDiv.appendChild(setEntityType); 
  embeddedCreateDiv.appendChild(entityTypeSelectorDiv);
  createNodeDiv.appendChild(embeddedCreateDiv);
  var startPositionInText = globalSelectionStart;   //pull from global scope
  var endPositionInText = globalSelectionEnd;       //pull from global scope
  var selectedString = globalSelectionText;         //pull from global scope
  var positionDiv = document.createElement('div');
  positionDiv.setAttribute('id', 'embeddedAnnotation');   // only exists here! Should be put right after annotationCreationDiv
  var positionTitle = document.createElement('h3');
  //console.log('annoinformationHere');
  positionTitle.appendChild(document.createTextNode('Annotation properties: '));
  setEntityType.addEventListener('change', function () {
    //clear out properties if they exist: 
    console.warn('triggered change in types');
    let e = event.source || event.target; 
    let d = document.getElementById('propertyBox');
    if (d !== null) { d.remove(); }
    loadPropertiesOfSelectedType(e.value);
    document.getElementById('annotationCreationDiv').classList.remove('hidden');
  })

  //      startposition
  var positionStart = document.createElement('p');
  var positionStartSpan = document.createElement('span');
  var startData = document.createElement('span');
  startData.appendChild(document.createTextNode(startPositionInText));
  startData.setAttribute('data-postname', startcode);
  positionStartSpan.appendChild(document.createTextNode('Starts: '));
  positionStartSpan.classList.add('font-bold');
  positionStart.appendChild(positionStartSpan);
  positionStart.appendChild(startData);
  //      endposition
  var positionEnd = document.createElement('p');
  var positionEndSpan = document.createElement('span');
  var endData = document.createElement('span');
  endData.setAttribute('data-postname', stopcode);
  endData.appendChild(document.createTextNode(endPositionInText));
  positionEndSpan.appendChild(document.createTextNode('Stops: '));
  positionEndSpan.classList.add('font-bold');
  positionEnd.appendChild(positionEndSpan);
  positionEnd.appendChild(endData);
  //    selection
  var selectedText = document.createElement('p');
  var selectedTextSpan = document.createElement('span');
  var textData = document.createElement('span');
  textData.appendChild(document.createTextNode(selectedString));
  selectedTextSpan.appendChild(document.createTextNode('Text: '));
  selectedTextSpan.classList.add('font-bold');
  selectedText.appendChild(selectedTextSpan);
  selectedText.appendChild(textData);
  //    add it to the block: 
  positionDiv.appendChild(positionTitle);
  positionDiv.appendChild(positionStart);
  positionDiv.appendChild(positionEnd);
  positionDiv.appendChild(selectedText);
  //positional info added: show spellingvariantbox: 
  //    allow the user to generate a list of spelling variants: 
 
  //spellingVariantDOMReturn = new SpellingVariant(null, null, globalLoginAvailable);

  checklogin()
  .then(valid => {
    console.log(valid); 
    //rst = valid['valid'];
    spellingVariantDOMReturn = new SpellingVariant(null, null, valid);
  })
  .catch(error => {
    spellingVariantDOMReturn = new SpellingVariant(null, null, false);
  })
  .finally(()=>{
    //code to create the WIKIDATA suggestion box has it's own function!!!!
    if(include_promptbox){
      return createWDPromptBox(createNodeDiv, positionDiv);
    }
  });
}

function createSideSkelleton() {
  const mainblock = document.getElementById('slideoverDynamicContent');
  mainblock.innerHTML = '';
  //user override/notice section: 
  /**
   * New userblock where the program informs the user of what's been found
   * in the backend/wikidata and how to proceed. 
   */
  const userblock = document.createElement('div');
  userblock.setAttribute('id', 'usermetablock'); 
  const notificationblock = document.createElement('div'); 
  notificationblock.setAttribute('id', 'usernotificationblock'); 
  const notificationpelement = document.createElement('p'); 
  const notificationkey = document.createElement('span'); 
  notificationkey.setAttribute('id', 'usernoticekey'); 
  notificationkey.classList.add('font-bold'); 
  const notificationvalue = document.createElement('span'); 
  notificationvalue.setAttribute('id', 'usernoticevalue'); 
  const overrideblock = document.createElement('div'); 
  overrideblock.setAttribute('id', 'overrideblock'); 
  //style the userblock distinctly from the rest: 

  //put it all together. 
  notificationpelement.appendChild(notificationkey);
  notificationpelement.appendChild(notificationvalue);
  notificationblock.appendChild(notificationpelement);
  userblock.appendChild(notificationblock);
  userblock.appendChild(overrideblock);
  //3 sections: 
  //1   Data section
  const textblock = document.createElement('div');
  textblock.setAttribute('id', 'topblock');
  textblock.innerHTML = '';
  //2   Variants section
  const middleblock = document.createElement('div');
  middleblock.setAttribute('id', 'neobox');
  middleblock.innerHTML = '';

  //    2.1:    relatedtextstats: shows amount of connections. 
  const statsTarget = document.createElement('div');
  statsTarget.setAttribute('id', 'relatedTextStats');
  statsTarget.classList.add('text-gray-600', 'w-full', 'm-2', 'p-2', 'left-0');
  statsTarget.innerHTML = '<p>HAS STATS??</p>';
  middleblock.appendChild(statsTarget);
  //    2.2:    variants: creates a div where the variants interaction is held. 
  const variantsTarget = document.createElement('div');
  variantsTarget.setAttribute('id', 'etVariantsTarget');
  variantsTarget.classList.add('text-gray-600', 'w-full', 'm-2', 'p-2', 'left-0');
  //variantsTarget.innerHTML = '<p>HAS VARS??</p>';
  //  2.2b:     URLS: creates a div where URL relations are held.
  const relationsTarget = document.createElement('div'); 
  const relationsTargetTitle = document.createElement('p'); 
  relationsTargetTitle.appendChild(createDivider('Knowledgebases: '));
  const triggerAddActionButton = document.createElement('button'); 
  triggerAddActionButton.appendChild(document.createTextNode('+ Add')); 
  triggerAddActionButton.classList.add('button'); 
  triggerAddActionButton.setAttribute('id', 'add_kb_relation'); 
  triggerAddActionButton.classList.add('btn', 'bg-green-100', 'hover:bg-green-300', 'font-bold', 'py-2', 'px-4', 'rounded-full');
  const relationsTargetSub = document.createElement('div'); 
  //TODO: urlrelations should be hidden when in nodecreationsmode
  relationsTargetSub.setAttribute('id', 'urlrelationscontainer'); 
  relationsTargetSub.classList.add('flex', 'border-t-2', 'border-t-dashed', 'flex-wrap'); 
  relationsTarget.appendChild(relationsTargetTitle); 
  relationsTarget.appendChild(triggerAddActionButton); 
  relationsTarget.appendChild(relationsTargetSub); 
  relationsTarget.setAttribute('id', 'urlrelations'); 
  relationsTarget.classList.add('text-gray-600', 'w-full', 'm-2', 'p-2', 'left-0', 'border-solid', 'border-2', 'border-black-800', 'rounded-md', 'flex-grow');
  middleblock.appendChild(variantsTarget);
  middleblock.appendChild(relationsTarget);
  //    2.3:    stablebox: sets stable identifier and link to explorer. 
  //3   Wikidata section. ==> content gets fully built by wd code.
  console.warn('SECTION 3 of top header: WDResponseTarget is disabled!!!'); 
  mainblock.appendChild(userblock); 
  mainblock.appendChild(textblock);
  mainblock.appendChild(middleblock);
}


function createMainBox(){
  var etMainBox = document.createElement('div');
  var etSubNavBox = document.createElement('div');
  var etSubContentBox = document.createElement('div');
  etMainBox.setAttribute('id', 'etmain');
  etSubNavBox.setAttribute('id', 'etnav');
  etSubContentBox.setAttribute('id', 'entitycontent');
  etMainBox.appendChild(etSubNavBox);
  etMainBox.appendChild(etSubContentBox);
  return etMainBox
}

function triggerSidePanelAction(entityData) {
  /*
      Side panel triggered when creating an entity from a non-annotated piece of text!
        ==> you don't have an entity yet!
        ==> you don't have a knowledgebaseyet! => pass false
        ==> you don't have a variant yet!
    BUT
        ==> You have the ability to call wikidata/ backend for matching options. 
        :> these matching options may hold entities! 
  */ 
  toggleSlide(1);
  //console.log(entityData);
  let = dataDictionary = {};
  createSideSkelleton();
  //You should not pass an entity ID to the KnowledgeBase constructor because this
  //code block is only triggered by unlinked annotations/new selections. So the first
  //argument is always false!
  checklogin()
    .then(valid => {
        kb = new KnowledgeBase(false, valid);
    })
    .catch(error => {
      kb = new KnowledgeBase(false, false);
    })
  //backend returned one or more nodes that have  spellingvariant/label matching the request: 
  // console.warn('BUG10: triggerSidePanelAction function');
  const topbox = document.getElementById('topblock');
  targetOfInfo = document.getElementById('slideoverDynamicContent');

    //detect if levenshtein is enabled: DO NOT rely on DOM; use the data itself
    let levscores = entityData['levenshtein_dist'];
    let weightscores = entityData['weights'];
    let hasLevenshtein = Object.keys(levscores).length > 0 
  if (entityData['nodes'].length) {
    //create a title that show the information about the matching entities: 
    let topTex = document.createElement('h3');
    topTex.classList.add('w-full');
    //create a box notice where the information is shown: 
    dataDictionary = entityData['nodes'];
    topTex.appendChild(document.createTextNode("Found " + dataDictionary.length + " nodes based on matching string."));
    topbox.appendChild(topTex);
    for (let k of Object.keys(dataDictionary)) {
      dataDictionary[k]['weight'] = entityData['weights'][dataDictionary[k][0]];
    }
    //BUG sort is not working! Disabled for now. 
    /*
    //sort the entities according to their score coming from the backend: 
    Object.keys(dataDictionary).sort(score);
    function score(a, b) {
      return dataDictionary[a]['weight'] + dataDictionary[b]['weight'];
    }*/
    //node with the heighest weight is presented first: >> load the first node: 
    var firstNode = dataDictionary[0];
    targetOfInfo.appendChild(createMainBox());
    let lev = false;
    let wght = false;
    if (hasLevenshtein){
      let nodeId = firstNode[0]; 
      lev = levscores[nodeId];
      wght = weightscores[nodeId];
    }
    const vardata = entityData['labelvariants']; 
    showET(firstNode, lev, wght, vardata, false);
    var datadictpage = 0;
    var pageLength = dataDictionary.length;

    function navET(dir) {
      //levenshtein scoring shown here to help user choose. 
      //navigates through the dataDictionary and picks a page(entity). 
      //only used when 2 or more possible entities are part of the selection.
      if (dir === '-') {
        //go back
        datadictpage--;
        if (datadictpage <= 0) {
          datadictpage = 0;
          document.getElementById('ETSuggestionArrowLeft').classList.add('invisible');
        }
      } else {
        //go up
        datadictpage++;
        if (datadictpage >= dataDictionary.length - 1) {
          datadictpage = dataDictionary.length - 1;
          document.getElementById('ETSuggestionArrowRight').classList.add('invisible');
        }
      }
      if (datadictpage != 0) {
        document.getElementById('ETSuggestionArrowLeft').classList.remove('invisible');
      }
      if (datadictpage != dataDictionary.length - 1) {
        document.getElementById('ETSuggestionArrowRight').classList.remove('invisible');
      }
      document.getElementById('xofindicator').innerHTML = datadictpage + 1;
      let lev = false;
      let wght = false;
      if (hasLevenshtein){
        let nodeId = dataDictionary[datadictpage][0]; 
        lev = levscores[nodeId];
        wght = weightscores[nodeId];
      }
      showET(dataDictionary[datadictpage], lev, wght, vardata);
    }

    if (Object.keys(dataDictionary).length > 1) {
      var navdisp = document.createElement('p');
      var xof = document.createElement('span');
      var navBlock1 = document.createElement('span');
      var navBlock2 = document.createElement('span');
      var navBlock3 = document.createElement('span');
      navBlock1.appendChild(document.createTextNode(datadictpage + 1));
      navBlock1.setAttribute('id', 'xofindicator');
      navBlock2.appendChild(document.createTextNode(' of '));
      navBlock3.appendChild(document.createTextNode(pageLength));
      xof.appendChild(navBlock1);
      xof.appendChild(navBlock2);
      xof.appendChild(navBlock3);
      document.getElementById('etnav').appendChild(navdisp);
      //create Nav arrow: 
      var prevET = document.createElement('span');
      var nextET = document.createElement('span');
      prevET.appendChild(document.createTextNode('<<'));
      prevET.classList.add('invisible');
      prevET.setAttribute('id', 'ETSuggestionArrowLeft');
      nextET.appendChild(document.createTextNode('>>'));
      nextET.setAttribute('id', 'ETSuggestionArrowRight');
      navdisp.appendChild(prevET);
      navdisp.appendChild(xof);
      navdisp.appendChild(nextET);
      prevET.addEventListener('click', function () { navET('-') })
      nextET.addEventListener('click', function () { navET('+') })
    }
    /*    not needed???: 16/11/2023  
    let midbox = document.createElement('div'); 
    midbox.classList.add('w-full'); 
    */
  } else {
    //nothing found in the backend: no matching variants or nodelabels: 
    updateState('State', 'The current database holds no nodes with matching spelling. Wikidata provides the following entities that match your annotation.')
    buildAnnotationCreationBox(); 
  }
}


function dropLatteSuggestion(segment){
  let spans = document.querySelectorAll('span[data-segment_id="' + segment + '"]');
  spans.forEach(ltr => {
    ltr.classList.remove('app_automatic', 'automatic_unstored');   //remove class that indicates it is an unstored node
    ltr.removeAttribute('data-segment_id');
    ltr.removeAttribute('data-entitytype');
    ltr.removeEventListener('click', clickHandler);
  });
  //remove the suggestion box
  ignoreSuggestion(); 
}

function makeBoxTemplate(){
  /**
   * creates a popup modal which can be calle dby other functions to customize further. 
   */
    if (event === undefined){return;}
    let targetElement = event.src || event.target;  
    let mode = 'stored';
    if(targetElement && targetElement.classList.contains('automatic_unstored')){
      var boxHeader = 'Unstored Annotation';
      var headerColor = 'bg-blue-300'; 
      mode = 'unstored';
    }else{
      var boxHeader = 'Entitites';
      var headerColor = 'bg-teal-300';
    }
    ignoreSuggestion();
    //external rangy library required!! 
    //https://github.com/timdown/rangy
    var topDst = rangy.getSelection().anchorNode.parentElement.offsetTop;
    var height = rangy.getSelection().anchorNode.parentElement.offsetHeight;
    var leftDst = rangy.getSelection().anchorNode.parentElement.offsetLeft - 125;
    if (leftDst < 10) {
      leftDst = 10;
    }

    //create div at fixed position: THIS IS ALWAYS REQUIRED
    var div = document.createElement('div');
    var tex = document.createTextNode(boxHeader);
    var texheader = document.createElement('H3');
    texheader.appendChild(tex);
    texheader.classList.add(headerColor, 'text-center', 'font-bold');
    div.appendChild(texheader);
    return [div, mode, topDst, height, leftDst];
}


function makeSuggestionBox() {
  /**
   * creates a box based on the current cursor position and shows
   * basic data/interaction about the element that triggered the 
   * annotation lookup. 
   * 
   * CONSIDER DROPPING THIS FUNCTIONALITY!
   */
  //special color scheme is used for unstored annotations that are 
  //found by the LATTE connector. Interface checks for the presence of the
  //automatic_unstored class in the classlist to determine how the layout
  // of the suggestionbox should be. 
  /*if (event === undefined){return;}
  let targetElement = event.src || event.target;  
  let mode = 'stored';
  if(targetElement && targetElement.classList.contains('automatic_unstored')){
    var boxHeader = 'Unstored Annotation';
    var headerColor = 'bg-blue-300'; 
    mode = 'unstored';
  }else{
    var boxHeader = 'Entitites';
    var headerColor = 'bg-teal-300';
  }
  ignoreSuggestion();
  //external rangy library required!! 
  //https://github.com/timdown/rangy
  var topDst = rangy.getSelection().anchorNode.parentElement.offsetTop;
  var height = rangy.getSelection().anchorNode.parentElement.offsetHeight;
  var leftDst = rangy.getSelection().anchorNode.parentElement.offsetLeft - 125;
  if (leftDst < 10) {
    leftDst = 10;
  }

  //create div at fixed position: THIS IS ALWAYS REQUIRED
  var div = document.createElement('div');
  var tex = document.createTextNode(boxHeader);
  var texheader = document.createElement('H3');
  texheader.appendChild(tex);
  texheader.classList.add(headerColor, 'text-center', 'font-bold');
  div.appendChild(texheader);*/
  //Spinner is only needed when working with stored annotations. 
  var [div, mode, topDst, height, leftDst] = makeBoxTemplate(); 
  if (mode === 'stored'){
    //create spinner;
    var spinner = document.createElement('div');
    spinner.innerHTML = '<div id="suggestionboxspinner" class="text-center m-1 p-1"> <svg role="status" class="inline w-8 h-8 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">         <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895  90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>  <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>  </svg> </div>';
    div.appendChild(spinner);
  }else{
    //var required; 
    var targetElement = event.src || event.target;  
  }

  div.classList.add('suggestionBox', 'bg-white');
  div.style.position = 'absolute';
  div.style.top = topDst + height + 'px';
  div.style.left = leftDst + 'px';
  div.style.minWidth = '250px';
  div.style.maxWidth = '300px';
  div.style.minHeight = '100px';
  div.style.maxHeight = '200px';
  div.setAttribute('id', 'suggestionOnSelect');
  //save/dismiss button:
  var buttonsBottom = document.createElement('div');
  var dismiss = document.createElement('button');
  dismiss.addEventListener('click', function () {
    ignoreSuggestion();
  });
  var dismisstext = document.createTextNode('Dismiss');
  buttonsBottom.classList.add('w-full', 'mt-auto', 'p-2', 'flex', 'justify-between');
  dismiss.classList.add('bg-red-400', 'p-1', 'rounded-sm', 'flex-grow');
  dismiss.appendChild(dismisstext);
  dismiss.setAttribute('id', 'suggestionbox_dismissButton');
  buttonsBottom.appendChild(dismiss);
  //when working with unstored annotations, add a button that stores the annotation
  //as an annotation_auto node in the database and assigns a UUIDV4 to it!
  if (mode === 'unstored' && targetElement.classList.contains('automatic_unstored')){
    var drop = document.createElement('button'); 
    drop.setAttribute('id', 'suggestionbox_dropButton'); 
    drop.addEventListener('click', function(){
      dropLatteSuggestion(targetElement.dataset.segment_id);
    })
    var droptext = document.createTextNode('reannotate'); 
    drop.appendChild(droptext); 
    drop.classList.add('bg-blue-400', 'p-1', 'rounded-sm', 'flex-grow', 'disabled:bg-blue-100', 'disabled:cursor-not-allowed'); 
    buttonsBottom.appendChild(drop); 
  }
  if (mode === 'unstored' && globalLoginAvailable){
    var save = document.createElement('button');
    save.addEventListener('click', function(){
      persistSuggestionOfLatteConnector(targetElement.dataset.segment_id);
    });
    var savetext = document.createTextNode('Store');
    save.classList.add('bg-green-400', 'p-1', 'rounded-sm','flex-grow', 'disabled:bg-green-100', 'disabled:cursor-not-allowed');
    save.setAttribute('id', 'suggestionbox_saveButton');
    save.appendChild(savetext);
    buttonsBottom.appendChild(save);
  } 
  div.appendChild(buttonsBottom);
  document.body.appendChild(div);
}

function handleKbs(kbs){
  document.getElementById('urlrelations'); 
}

function loadIntoSuggestionBox(data, from, to) {
  // console.log('data', data);
  // console.log('from', from);
  // console.log('to', to);
  var datadiv = document.createElement('div');
  var metadataOnSearch = document.createElement('div');
  metadataOnSearch.classList.add('suggestionMetadata');
  var dataOnSearch = document.createElement('div');
  dataOnSearch.classList.add('suggestionData');
  var edgesInfo = document.createElement('p');
  var keySpanEdge = document.createElement('span');
  var edgesKey = document.createTextNode('Edges: ');
  keySpanEdge.appendChild(edgesKey);
  var nodesInfo = document.createElement('p');
  var keySpanNode = document.createElement('span');
  var nodesKey = document.createTextNode('Nodes: ');
  keySpanNode.appendChild(nodesKey);
  keySpanNode.classList.add('font-bold');
  keySpanEdge.classList.add('font-bold');
  var retrievedCoreElements = data.nodes.filter(node => coreNodes.includes(node[1]));
  var valueSpanEdge = document.createTextNode(data.edges.length);
  var valueSpanNode = document.createTextNode(data.nodes.length + ' | ' + retrievedCoreElements.length);
  var positionBox = document.createElement('p');
  positionBox.appendChild(document.createTextNode('Start: '));
  positionBox.appendChild(document.createTextNode(from));
  positionBox.appendChild(document.createTextNode(' || '));
  positionBox.appendChild(document.createTextNode('End: '));
  positionBox.appendChild(document.createTextNode(to));
  nodesInfo.appendChild(keySpanNode);
  nodesInfo.appendChild(valueSpanNode);
  edgesInfo.appendChild(keySpanEdge);
  edgesInfo.appendChild(valueSpanEdge);
  metadataOnSearch.appendChild(nodesInfo);
  metadataOnSearch.appendChild(edgesInfo);
  metadataOnSearch.appendChild(positionBox);
  datadiv.appendChild(metadataOnSearch);
  //insert the search results here:
  triggerSidePanelAction(data);
  datadiv.appendChild(dataOnSearch);
  if(datamode === 'stored'){
    document.getElementById("suggestionboxspinner").parentNode.insertBefore(datadiv, document.getElementById('suggestionboxspinner'));
    document.getElementById('suggestionboxspinner').remove();
  }
  handleKbs(data['silo']); 
}

function getTextSelection() {
  //you need a map filter on selection based on length of childnodes!
  //In some cases you can get undefined back when the selecting happens in the wrong DOM
  //if that's the case, just return false!
  var selection = rangy.getSelection().getRangeAt(0).getNodes().filter(s => s.childNodes.length == 0);
  if (selection.length > 0) {
    //get first and last selection elements to extract data attribute:
    globalSelectionText = rangy.getSelection().toString();
    globalSelectionStart = parseInt(selection[0].parentElement.dataset.itercounter);
    globalSelectionEnd = parseInt(selection[selection.length - 1].parentElement.dataset.itercounter);
    //FIXED: on Windows systems the trailing space and newline symbol is part of entity text: 
    let trimmedText = globalSelectionText.trim()
    if (trimmedText !== globalSelectionText) {
      while (!(globalSelectionText.startsWith(trimmedText))) {
        globalSelectionStart += 1;
        globalSelectionText = globalSelectionText.slice(1, globalSelectionText.length);
      }
      while (!(globalSelectionText.endsWith(trimmedText))) {
        globalSelectionEnd -= 1;
        globalSelectionText = globalSelectionText.slice(0, globalSelectionText.length - 1);
      }
    }

    return [globalSelectionText, globalSelectionStart, globalSelectionEnd];
  } else {
    return false;
  }
}

function open_ld_maxhits(){
  let isChecked = document.getElementById('use_ld').checked; 
  if (isChecked){
    document.getElementById('max_ld').classList.remove('hidden');
    document.getElementById('max_ld_tip').classList.remove('hidden');
  }else{
    document.getElementById('max_ld').classList.add('hidden');
    document.getElementById('max_ld_tip').classList.add('hidden');
  }
}


function triggerSelection() {
  unmark()
  var selectedTextProperties = getTextSelection();
  var selectedText = selectedTextProperties[0];
  var selectedTextStart = selectedTextProperties[1];
  var selectedTextEnd = selectedTextProperties[2];
  //Always set datamode to null when you select an et and go through the manual annotation proces. 
  datamode = null;

  //fetch from BE:
  if (selectedText) {
    //get parameters for levenshtein bool and ints
    $baseURL = '/AJAX/getEntitySuggestion.php?';
    $parameters = {
      'type': '',    //type is empty as there was no pickup by NERtool
      'value': selectedText,
      'casesensitive': false, 
      'allow_levenshtein': $('#use_ld').is(":checked"), 
      'levenshtein_items': $('#max_ld').val()
    };
    $sendTo = $baseURL + jQuery.param($parameters);
    //Trigger when selecting raw text. 
    makeSuggestionBox();
    getInfoFromBackend($sendTo)
      .then((data) => {
        loadIntoSuggestionBox(data, selectedTextStart, selectedTextEnd);
        //handleKbs(data['silo']); 
      })
  }
}

$(document).ready(function () {
  document.getElementById('textcontent').addEventListener('mouseup', function () { triggerSelection() });
  document.getElementById('textcontent').addEventListener('keyup', function () { triggerSelection() });
  //use esc key to delete the suggestionbox:
  document.addEventListener('keyup', function (event) {
    if (event.key === 'Escape') {
      ignoreSuggestion();
    }
  });
});
