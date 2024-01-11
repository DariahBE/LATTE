function ignoreSuggestion() {
  var isOpen = document.getElementById("suggestionOnSelect");
  if (isOpen) {
    isOpen.remove();
  }
  toggleSlide(0);
}

function extractAnnotationPropertiesFromDOM(domBlock) {
  let prop = {}
  for (let i = 0; i < domBlock.length; i++) {
    let box = domBlock[i].getElementsByClassName('inputelement')[0];
    //console.log(box);
    //todo => boxes that are checkboxes should use .checked not .value method
    let boxName = box.name;
    console.log('eetse beetsy bugfixing required!');
    let boxValue = extractValueType(box);
    console.log(boxName, boxValue);
    prop[boxName] = boxValue;
  }
  return prop;
}


function typeToHtml(type, defaultValue = 'text') {
  //converts configured type to valid html types:
  const conversionList = {
    'longtext': false,
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

function saveNewDB() {
  let mistakes = document.getElementsByClassName('validatorFlaggedMistake');
  //IS erverything valid 
  //validate in backend too!!
  let dataObject = {};
  if (mistakes.length == 0) {
    //  Y
    console.log('no mistakes made.');
    //get a CSRF token
    fetch("/AJAX/getdisposabletoken.php")
      .then(response => response.json())
      .then(data => {
        const token = data;
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
        /////// PROPERTIES: 
        let properties = {};
        const etType = document.getElementById('entityTypeSelector').value;
        console.log('CHOSEN ET: ', etType); 
        let propertyPairs = document.getElementById('propertyBox').getElementsByTagName('div');
        console.log(propertyPairs);
        for (p = 0; p < propertyPairs.length; p++) {
          let pair = propertyPairs[p];
          let pairName = pair.getElementsByTagName('input')[0].name;
          let pairValue = pair.getElementsByTagName('input')[0].value;
          properties[pairName] = pairValue;
        }

        //appending to dataObject
        dataObject['token'] = token;
        dataObject['texid'] = languageOptions['nodeid'];
        dataObject['nodetype'] = etType;
        //let annotationCollectionBox = {};
        let annotationProperties = document.getElementById('annotationCreationDiv').getElementsByClassName('property');
        let annotationCollectionBox = extractAnnotationPropertiesFromDOM(annotationProperties)
        /*for(let i = 0; i < annotationProperties.length; i++){
          let box = annotationProperties[i].getElementsByClassName('inputelement')[0];
          //console.log(box);
          //todo => boxes that are checkboxes should use .checked not .value method
          let boxName = box.name; 
          console.log('eetse beetsy bugfixing required!');
          let boxValue = extractValueType(box);
          console.log(boxName, boxValue);
          annotationCollectionBox[boxName] = boxValue;
        }*/
        annotationCollectionBox[startcode] = startOfSelection;
        annotationCollectionBox[stopcode] = endOfSelection;

        dataObject['annotation'] = annotationCollectionBox;
        dataObject['variants'] = foundVariants;
        dataObject['properties'] = properties;
        console.log('Sending to server: ');
        console.log("savenewdb", dataObject);
        //send dataobject to backend: 
        console.warn('sending data:');
        $.post("/AJAX/put_annotation.php", { data: dataObject }, function (data, status) {
          console.log(data);
          console.log(status);
        });
      }); 
      //delete the save-button from DOM: 
      document.getElementById('saveEtToDb').setAttribute('disabled', true); // Prevents dual submission!
      //append token to request
  } else {
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
    document.getElementById('slideover-container').classList.add('invisible');
    document.getElementById('slideover').classList.add('translate-x-full');

  } else {
    document.getElementById('slideover-container').classList.remove('invisible');
    document.getElementById('slideover').classList.remove('translate-x-full');
  }
}

function loadPropertiesOfSelectedType(selectedString) {
  //reads from the DOM which entity is being created; 
  //does an AJAX call to fetch the structure of the entity and matches with config file
  //fields get generated and appended. If old fields exist, they are removed. 
  let selector = document.getElementById('entityTypeSelector');
  let selected = selector.value; //dropdown value selected. 
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
        //console.log(nodedata); 
        Object.entries(nodedata).forEach(entry => {
          const [key, value] = entry;
          console.log(key, value);
          var humanLabel = value[0];
          var datatype = value[1];
          let newFieldContainer = document.createElement('div');
          let newFieldLabel = document.createElement('label');
          let newFieldInput;
          if (datatype === 'longtext') {
            newFieldInput = document.createElement('textarea');
          } else {
            newFieldInput = document.createElement('input');
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
          let htmlType = typeToHtml(datatype);
          if (htmlType !== false) {
            newFieldInput.setAttribute('type', htmlType);
          }
          let expectedPattern = typeToPattern(datatype);
          if (expectedPattern) {
            newFieldInput.setAttribute('pattern', expectedPattern);
          }
          newFieldInput.classList.add('attachValidator');
          newFieldInput.classList.add('validateAs_' + datatype);
          newFieldInput.classList.add('border', 'border-gray-300', 'text-gray-900', 'rounded-lg', 'p-2.5');
          newFieldContainer.appendChild(newFieldLabel);
          newFieldContainer.appendChild(newFieldInput);
          formBox.appendChild(newFieldContainer);
        });
        //formBox.appendChild(formBoxHeader);
        selector.parentElement.appendChild(formBox);
        //attach validator: 
        let validator = new Validator;
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
          //console.log(nodedata); 
          Object.entries(nodedata).forEach(entry => {
            const [key, value] = entry;
            var humanLabel = value[0];
            var datatype = value[1];
            let newFieldContainer = document.createElement('div');
            let newFieldLabel = document.createElement('label');
            let newFieldInput;
            if (datatype === 'longtext') {
              newFieldInput = document.createElement('textarea');
            } else {
              newFieldInput = document.createElement('input');
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
            let htmlType = typeToHtml(datatype);
            if (htmlType !== false) {
              newFieldInput.setAttribute('type', htmlType);
            }
            let expectedPattern = typeToPattern(datatype);
            if (expectedPattern) {
              newFieldInput.setAttribute('pattern', expectedPattern);
            }
            newFieldInput.classList.add('attachValidator');
            newFieldInput.classList.add('validateAs_' + datatype);
            newFieldInput.classList.add('border', 'border-gray-300', 'text-gray-900', 'rounded-lg', 'p-2.5');
            newFieldContainer.appendChild(newFieldLabel);
            newFieldContainer.appendChild(newFieldInput);
            fieldContents.push(newFieldContainer);
          });
        }
        resolve(fieldContents);
      })
  })
}

function showET(etdata) {
  let wd = null;
  let wdboxToDrop = document.getElementById('WDResponseTarget');
  if (wdboxToDrop) { wdboxToDrop.remove(); }
  const subtarget = document.getElementById('entitycontent');
  subtarget.innerHTML = '';
  //console.log(etdata);
  var label = etdata[1];
  var properties = etdata[2];
  //console.log('propers: '); 
  //console.log(properties);
  var propdiv = document.createElement('div');
  for (let k in properties) {
    let show = null;
    let key = k;
    let value = properties[k];
    let valueType = value['vartype'];
    let valueDOM = value['DOMString'];
    let datavalue = value['value'];
    if (valueType == 'uri') {
      show = generateHyperlink(valueDOM, datavalue, ['externalURILogo']);
    } else if (valueType == 'wikidata' && datavalue !== null) {
      //console.log("wikidata box: ");
      //console.log(value);
      show = document.createElement('p')
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
  document.getElementById('entitycontent').appendChild(propdiv);
  var wikidataID = etdata[3];
  if (wikidataID) {
    wd = new wikibaseEntry(wikidataID, wdProperties, 'slideover', 'qid');
    wd.getWikidata()
      .then(function () { wd.renderEntities(wikidataID) });
  }
  //with the data displayed: allow the user to accept the suggestion => this creates a new annotation between
  //the text and existing ET. 
  //console.log('accept/reject suggestion'); 
  var d = document.getElementById('assignEtToSelection');
  if (d !== null) { d.remove(); }
  fetch('/user/AJAX/profilestate.php')
    .then((response) => response.json())
    .then((data) => {
      console.log('profilestate', data);
      if (data['valid']) {
        var csrf = data['csrf'];
        var acceptLink = document.createElement('button');
        acceptLink.setAttribute('id', 'assignEtToSelection')
        //console.log(selectedText, selectedTextStart,  selectedTextEnd); 
        var acceptText = document.createTextNode('Create annotation');
        acceptLink.appendChild(acceptText);
        acceptLink.classList.add('bg-green-400');
        acceptLink.addEventListener('click', function () {
          //make button unresponsive: 
          acceptLink.disabled = true;
          //data to send to server
          //read the content of the div that holds annotation data when connecting nodes 
          let annotationProperties = document.getElementById('annotationCreationDiv').getElementsByClassName('property');
          let annotationCollectionBox = extractAnnotationPropertiesFromDOM(annotationProperties);
          let postData = {
            sourceNeoID: etdata[0],
            texNeoid: languageOptions['nodeid'],
            csrf: csrf,
            start: globalSelectionStart,
            stop: globalSelectionEnd,
            selection: globalSelectionText
          };
          $.ajax({
            type: "POST",
            url: "/AJAX/crud/connect.php",
            data: postData,
            dataType: "json",
            success: function (repldata) {
              //console.log(data); 
              //let repldata = JSON.parse(json);
              console.log(repldata);
              let repl = document.createElement('p');
              repl.appendChild(document.createTextNode(repldata['msg']));
              document.getElementById('etmain').appendChild(repl);
              let annotationStart = repldata['start'];
              let annotationEnd = repldata['stop'];
              let annotationUID = repldata['annotation'];
              let annotationForType = repldata['type'];
              console.warn('UNUSED variables of CRUD/Connect call!!: ', annotationStart, annotationEnd, annotationUID, annotationForType);
            }
          }).always(
            function () {
              document.getElementById('assignEtToSelection').remove(); //delete annotation button
            }
          )
        });
        //calls a helper function that generates the input elements
        //according to their type. All elements are then added to
        //annotationProperties. 
        annotationProperties = document.createElement('div');
        annotationProperties.setAttribute('id', 'annotationCreationDiv');
        annoPromptTitle = document.createElement('h3');
        annoPromptTitle.appendChild(document.createTextNode('Annotation properties:'));
        annotationProperties.appendChild(annoPromptTitle);
        annoSubContent = document.createElement('div');
        buildPropertyInputFieldsFor('Annotation').then((content) => {
          for (let i = 0; i < Object.keys(content).length; i++) {
            //don't show: start, stop, selectedtext. 
            let field = content[i];
            let fieldAtr = field.getElementsByTagName('label')[0].getAttribute('for');
            if (fieldAtr != startcode && fieldAtr != stopcode) {
              annoSubContent.appendChild(field);
            }
          }
          annotationProperties.appendChild(annoSubContent);

          document.getElementById('etmain').appendChild(annotationProperties);
          //attach validator after content is in the DOM:  
          let validator = new Validator;
          validator.pickup();

        });
        //make a save button to commit the data: 
        let saveNewEntry = document.createElement('button');
        saveNewEntry.setAttribute('id', 'saveEtToDb');
        saveNewEntry.classList.add('bg-green-400', 'mx-2', 'px-2', 'my-1', 'py-1', 'rounded');
        saveNewEntry.appendChild(document.createTextNode('Save'));
        saveNewEntry.addEventListener('click', function () {
          saveNewDB();
        });
        document.getElementById('etmain').appendChild(acceptLink);
      }
    })

}


function createSideSkelleton() {
  const mainblock = document.getElementById('slideoverDynamicContent');
  mainblock.innerHTML = '';
  //3 sections: 
  //1   Data section
  const textblock = document.createElement('div');
  textblock.setAttribute('id', 'topblock');
  textblock.innerHTML = '<p>HAS CONTENT (topblock)??</p>';
  //2   Variants section
  const middleblock = document.createElement('div');
  middleblock.setAttribute('id', 'neobox');
  middleblock.innerHTML = '<p>HAS CONTENT (neobox)??</p>';

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
  variantsTarget.innerHTML = '<p>HAS VARS??</p>';
  middleblock.appendChild(variantsTarget);
  //    2.3:    stablebox: sets stable identifier and link to explorer. 
  //3   Wikidata section. ==> content gets fully built by wd code.
  const wdblock = document.createElement('div');
  wdblock.innerHTML = '<p>HAS WD??</p>';
  wdblock.setAttribute('id', 'WDResponseTarget');
  wdblock.classList.add('border-t-2', 'mt-1', 'pt-1');
  mainblock.appendChild(textblock);
  mainblock.appendChild(middleblock);
  mainblock.appendChild(wdblock);
}

function triggerSidePanelAction(entityData) {
  toggleSlide(1);
  //console.log(entityData);
  let = dataDictionary = {};
  createSideSkelleton();
  //backend returned one or more nodes that have  spellingvariant/label matching the request: 
  // console.warn('BUG10: triggerSidePanelAction function');
  const targetOfInfo = document.getElementById('slideoverDynamicContent');
  const topbox = document.getElementById('topblock');
  if (entityData['nodes'].length) {
    //create a title that show the information about the matching entities: 
    let topTex = document.createElement('h3');
    topTex.classList.add('w-full');
    //create a box notice where the information is shown: 
    //find a way of attaching variants to the nodes!!
    //start with interpreting the edges: connect the entitynode with the variants once you know that!
    //BUG: entityID gets repeated on one to many relations with variants!
    dataDictionary = entityData['nodes'];

    topTex.appendChild(document.createTextNode("Found " + dataDictionary.length + " nodes based on matching string."));
    topbox.appendChild(topTex);
    for (let k of Object.keys(dataDictionary)) {
      dataDictionary[k]['weight'] = entityData['weights'][dataDictionary[k][0]];
    }
    //sort the entities according to their score coming from the backend: 
    Object.keys(dataDictionary).sort(score);
    function score(a, b) {
      return dataDictionary[a]['weight'] - dataDictionary[b]['weight'];
    }
    //node with the heighest weight is presented first: >> load the first node: 
    var firstNode = dataDictionary[0];
    var etMainBox = document.createElement('div');
    var etSubNavBox = document.createElement('div');
    var etSubContentBox = document.createElement('div');
    etMainBox.setAttribute('id', 'etmain');
    etSubNavBox.setAttribute('id', 'etnav');
    etSubContentBox.setAttribute('id', 'entitycontent');
    etMainBox.appendChild(etSubNavBox);
    etMainBox.appendChild(etSubContentBox);
    targetOfInfo.appendChild(etMainBox);
    showET(firstNode);
    var datadictpage = 0;
    var pageLength = dataDictionary.length;

    function navET(dir) {
      alert('moving ET');
      //navigates through the dataDictionary and picks a page(entity). 
      //only used when 2 or more possible entities are part of the selection.
      //TODO: update of DOM isn't working!
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
      showET(dataDictionary[datadictpage]);
      //OK
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
    var createNodeDiv = document.createElement('div');
    createNodeDiv.classList.add('w-full');
    createNodeDiv.setAttribute('id', 'etcreate');
    var embeddedCreateDiv = document.createElement('div');
    embeddedCreateDiv.setAttribute('id', 'embeddedET');
    embeddedCreateDiv.classList.add('hidden');
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
        //exclude: start, stop and selectedtext info!
        Object.entries(data['data']).forEach(entry => {
          const [key, value] = entry;
          var humanLabel = value[0];
          var datatype = value[1];
          if (data['exclude'] && data['exclude'].includes(key)) {
            //do not use the key:
          } else {
            //console.warn('new key created for: ', key); 
            //console.log(key, datatype);
            let newFieldContainer = document.createElement('div');
            newFieldContainer.classList.add('property');
            let newFieldLabel = document.createElement('label');
            newFieldLabel.appendChild(document.createTextNode(humanLabel));
            let newFieldInput;
            if (datatype === 'longtext') {
              newFieldInput = document.createElement('textarea');
            } else {
              newFieldInput = document.createElement('input');
            }
            newFieldInput.classList.add('inputelement');
            newFieldLabel.setAttribute('for', key);
            newFieldInput.setAttribute('name', key);
            let htmlType = typeToHtml(datatype);
            if (htmlType !== false) {
              newFieldInput.setAttribute('type', htmlType);
            }
            newFieldInput.setAttribute('type', htmlType);
            let expectedPattern = typeToPattern(datatype);
            if (expectedPattern) {
              newFieldInput.setAttribute('pattern', expectedPattern);
            }
            newFieldInput.classList.add('attachValidator');
            newFieldInput.classList.add('validateAs_' + datatype);
            newFieldInput.classList.add('border', 'border-gray-300', 'text-gray-900', 'rounded-lg', 'p-2.5');
            newFieldContainer.appendChild(newFieldLabel);
            newFieldContainer.appendChild(newFieldInput);
            topBox.appendChild(newFieldContainer);
          }
        });
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
    var entityTypePrompt = document.createElement('p');
    entityTypePrompt.classList.add('text-lg', 'p-2', 'm-2');
    //console.warn('Related to BUG10: race condition.');
    entityTypePrompt.appendChild(document.createTextNode('1) Set entity type: '));
    entityTypeDiv.appendChild(entityTypePrompt);
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
    embeddedCreateDiv.appendChild(setEntityType);
    createNodeDiv.appendChild(embeddedCreateDiv);
    /* OK: undefined errors when starting selection from outside the text div. 
      //Dropdown added: Show positional info: 
      //var text = getTextSelection();
      //globalSelectionText, globalSelectionStart, globalSelectionEnd;
      var startPositionInText = text[1];
      var endPositionInText = text[2];
      var selectedString = text[0];
    */
    //bugfix ==> When clicking outside the text div and having a selection
    //old code would cause undefined errors! This works. 
    console.log('Bug when clicking recognized unlinked ets.', startPositionInText, globalSelectionStart); 
    var startPositionInText = globalSelectionStart;
    var endPositionInText = globalSelectionEnd;
    var selectedString = globalSelectionText;
    var positionDiv = document.createElement('div');
    positionDiv.setAttribute('id', 'embeddedAnnotation');
    var positionTitle = document.createElement('h3');
    //console.log('annoinformationHere');
    positionTitle.appendChild(document.createTextNode('Annotation information: '));
    setEntityType.addEventListener('change', function () {
      //clear out properties if they exist: 
      let d = document.getElementById('propertyBox');
      if (d !== null) { d.remove(); }
      loadPropertiesOfSelectedType(selectedString);
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
    /*
    console.warn("code should be rewritten to use function call to displayWrittenVariants()");
    var spellingVariantTracker = [];
    var spellingVariantMainBox = document.createElement('div');
    spellingVariantMainBox.setAttribute('id', 'embeddedSpellingVariants');
    var spellingVariantTitle = document.createElement('h3'); 
    spellingVariantTitle.appendChild(document.createTextNode('Naming variants: '));
    spellingVariantTitle.classList.add('font-bold', 'text-lg', 'items-center', 'flex', 'justify-center');
    spellingVariantMainBox.appendChild(spellingVariantTitle);
    spellingVariantMainBox.classList.add('border-solid', 'border-2', 'border-black-800', 'rounded-md', 'flex-grow'); 
    var spellingVariantCreation = document.createElement('input'); 
    spellingVariantCreation.setAttribute('id', 'variantInputBox'); 
    spellingVariantCreation.classList.add('border-solid', 'border-2')
    var spellingVariantSubBox = document.createElement('div');
    spellingVariantSubBox.setAttribute('id', 'variantStorageBox'); 
    spellingVariantSubBox.classList.add('flex', 'border-t-2', 'border-t-dashed', 'flex', 'flex-wrap');
    var addToStorageBox = document.createElement('button'); 
    addToStorageBox.appendChild(document.createTextNode('Add')); 
    addToStorageBox.addEventListener('click', function(){
      var writtenValue = document.getElementById('variantInputBox').value; 
      document.getElementById('variantInputBox').value = ''; 
      if(spellingVariantTracker.includes(writtenValue)){
        return;
      }
      spellingVariantTracker.push(writtenValue);
      var storeIn = document.getElementById('variantStorageBox'); 
      var variantDisplayDiv = document.createElement('div'); 
      variantDisplayDiv.classList.add('m-1','p-1','spellingvariantbox', 'bg-amber-100', 'flex');
      var variantDisplayTex = document.createElement('p');
      variantDisplayTex.classList.add('writtenvariantvalue');
      variantDisplayTex.appendChild(document.createTextNode(writtenValue));
      var variantDisplayBin = document.createElement('p');
      variantDisplayBin.classList.add('xsbinicon', 'bg-amber-200', 'm-1','p-1', 'rounded-full'); 
      variantDisplayBin.addEventListener('click', function(){binVariant(this);});
      variantDisplayDiv.appendChild(variantDisplayTex);
      variantDisplayDiv.appendChild(variantDisplayBin);
      storeIn.appendChild(variantDisplayDiv);
    }); 
    spellingVariantMainBox.appendChild(spellingVariantCreation);
    spellingVariantMainBox.appendChild(addToStorageBox);
    spellingVariantMainBox.appendChild(spellingVariantSubBox); */
    //DELETED:16/11/2023 //let spellingVariantDOMReturn = spellingVariantCreation(null); 
    //OK : this variant spelling data needs to be performed by a call to et_variants > displayET_variant()

    /*var gateWay = document.createElement('div');
    gateWay.setAttribute('id', 'neobox');
    var statsTarget = document.createElement('div');
    statsTarget.setAttribute('id', 'relatedTextStats');
    statsTarget.classList.add('text-gray-600', 'w-full',  'm-2', 'p-2', 'left-0');
    //annotationTarget.innerHTML = '';
    gateWay.appendChild(statsTarget);
    var variantsTarget = document.createElement('div'); 
    variantsTarget.setAttribute('id', 'etVariantsTarget')
    variantsTarget.classList.add('text-gray-600', 'w-full',  'm-2', 'p-2', 'left-0');
    gateWay.appendChild(variantsTarget); 
    targetOfInfo.appendChild(gateWay); */
    let spellingVariantDOMReturn = displayET_variant(null, null);
    //variantbox has to be invisible in this phase: entity still needs to be created!!
    document.getElementById('embeddedSpellingVariants').classList.add('hidden');
    //TODO!!: alert('#embeddedSpellingVariants is hidden, make visible again when ET is created.'); 
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
    console.log('creating wdibox.'); 
    wikidataInputBox.classList.add('border', 'border-gray-300', 'rounded-md', 'shadow-sm', 'focus:outline-none', 'focus:border-indigo-500');
    wikidataInputBox.value = selectedString;
    wikidataRowBox.appendChild(wikidataInputBox);
    var searchButtonForWDPrompt = document.createElement('button');
    var searchButtonForWDPromptText = document.createTextNode('Search');
    searchButtonForWDPrompt.classList.add('bg-green-500', 'border-solid', 'hover:bg-green-600', 'p-2', 'm-2', 'rounded-lg', 'text-white', 'font-bold');
    searchButtonForWDPrompt.appendChild(searchButtonForWDPromptText);
    searchButtonForWDPrompt.addEventListener('click', function () {
      //console.log('make function call get the preferred lookup language!'); 
      //console.log('lookup and display can be connected!'); 
      wdprompt(wikidataInputBox.value, 0);
    });
    var wikidataResultsBox = document.createElement('div');
    wikidataResultsBox.setAttribute('id', 'wdpromptBox');
    wikidataPromptMainbox.appendChild(wikidataQLabel);
    wikidataPromptMainbox.appendChild(wikidataRowBox);
    wikidataPromptMainbox.appendChild(searchButtonForWDPrompt);
    wikidataPromptMainbox.appendChild(wikidataResultsBox);


    //add all boxes to the DOM: 
    createNodeDiv.appendChild(positionDiv);
    //createNodeDiv.appendChild(spellingVariantMainBox);
    createNodeDiv.appendChild(spellingVariantDOMReturn);
    //add a WD Promptbox and trigger the function for wikidata_prompting from here:
    createNodeDiv.appendChild(wikidataPromptMainbox);
    searchButtonForWDPrompt.click();
    //done with spelling variants: 
  }
}



function makeSuggestionBox() {
  ignoreSuggestion();
  var topDst = rangy.getSelection().anchorNode.parentElement.offsetTop;
  var height = rangy.getSelection().anchorNode.parentElement.offsetHeight;
  var leftDst = rangy.getSelection().anchorNode.parentElement.offsetLeft - 125;
  if (leftDst < 10) {
    leftDst = 10;
  }
  //create spinner;
  var spinner = document.createElement('div');
  spinner.innerHTML = '<div id="suggestionboxspinner" class="text-center"> <svg role="status" class="inline w-8 h-8 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">         <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895  90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>  <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>  </svg> </div>';
  //create div at fixed position:
  var div = document.createElement('div');
  var tex = document.createTextNode('Entities: ');
  var texheader = document.createElement('H3');
  texheader.appendChild(tex);
  texheader.classList.add('bg-teal-300', 'flex', 'justify-content');
  div.appendChild(texheader);
  div.appendChild(spinner);
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
  /*var save = document.createElement('button');
  save.addEventListener('click', function(){saveSuggestion();});
  var savetext = document.createTextNode('Save');*/
  var dismiss = document.createElement('button');
  dismiss.addEventListener('click', function () {
    ignoreSuggestion();
  });
  var dismisstext = document.createTextNode('Dismiss');
  buttonsBottom.classList.add('w-full', 'mt-auto', 'p-2');
  //save.disabled = true;
  //save.classList.add('bg-green-400', 'w-1/2', 'disabled:opacity-25', 'disabled:cursor-not-allowed');
  //save.setAttribute('id', 'suggestionbox_saveButton');
  dismiss.classList.add('bg-red-400', 'w-1/2');
  //save.appendChild(savetext);
  dismiss.appendChild(dismisstext);
  dismiss.setAttribute('id', 'suggestionbox_dismissButton');
  //buttonsBottom.appendChild(save);
  buttonsBottom.appendChild(dismiss);
  div.appendChild(buttonsBottom);
  document.body.appendChild(div);
}

function loadIntoSuggestionBox(data, from, to) {
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
  console.log(data);
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
  document.getElementById("suggestionboxspinner").parentNode.insertBefore(datadiv, document.getElementById('suggestionboxspinner'));
  document.getElementById('suggestionboxspinner').remove();
}

let globalSelectionText = null;
let globalSelectionStart = null;
let globalSelectionEnd = null;
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

function triggerSelection() {
  console.log('call into triggerselection()');
  var selectedTextProperties = getTextSelection();
  console.log('callresult', selectedTextProperties);
  var selectedText = selectedTextProperties[0];
  console.log('Properties: ', selectedTextProperties);
  var selectedTextStart = selectedTextProperties[1];
  var selectedTextEnd = selectedTextProperties[2];

  //fetch from BE:
  if (selectedText) {
    $baseURL = '/AJAX/getEntitySuggestion.php?';
    $parameters = {
      'type': '',    //type is empty as there was no pickup by NERtool
      'value': selectedText,
      'casesensitive': false
    };
    $sendTo = $baseURL + jQuery.param($parameters);
    makeSuggestionBox();
    getInfoFromBackend($sendTo)
      .then((data) => {
        loadIntoSuggestionBox(data, selectedTextStart, selectedTextEnd);
      })
  }
}

$(document).ready(function () {
  // bug: if cursor lets go off the letter, trigger doesn't work, attach it higher up!
  document.getElementById('textcontent').addEventListener('mouseup', function () { triggerSelection() });
  document.getElementById('textcontent').addEventListener('keyup', function () { triggerSelection() });
  //use esc key to delete the suggestionbox:
  document.addEventListener('keyup', function (event) {
    if (event.key === 'Escape') {
      ignoreSuggestion();
    }
  });
});
