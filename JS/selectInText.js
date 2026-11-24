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
  if (!target) return;
  target.innerHTML = ''; 
  let warningDiv = document.createElement('div'); 
  warningDiv.classList.add('notice', 'w-full'); 
  let warningParagraph = document.createElement('p');
  let warningText = document.createTextNode('This feature is disabled for non-registered users. For the current account policy, refer to ');
  let warningLinkText = document.createTextNode('this page');
  let warningLink = document.createElement('a'); 
  warningLink.setAttribute('href', '/register.php'); 
  warningLink.setAttribute('target', '_blank'); 
  warningLink.appendChild(warningLinkText); 
  warningParagraph.appendChild(warningText); 
  warningParagraph.appendChild(warningLink); 
  target.appendChild(warningParagraph); 
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
    'wikidata': '^Q[0-9]+$',
  };
  // Check if the type exists in the conversionList, if yes, return the corresponding pattern
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
  for (let i = start; i <= stop; i++) {
    const e = (typeof getSpanByIter === 'function') ? getSpanByIter(i) : document.querySelector(`.ltr[data-itercounter="${i}"]`);
    if (e) {
      e.classList.add(type, 'linked', 'underline');
      e.classList.remove('app_automatic', 'automatic_unstored', 'markedAnnotation');
      e.dataset.annotation = uuid;
      e.dataset.annotationCount = 1;
    }
  }
}

function saveNewDB() {
  /**
   * Function that saves a newly created entity in the Database.
   * Triggered when a text selection is not recognized as an existing ET, 
   * will create a new ET, ANNO and variant node.
   */
  let mistakes = document.getElementsByClassName('validatorFlaggedMistake');
  if (typeof validator !== 'undefined' && validator && typeof validator.checkRequired === 'function') {
    validator.checkRequired(); 
  }
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
        const startOfSelection = parseInt(globalSelectionStart, 10);
        //get end position of annotation: 
        const endOfSelection = parseInt(globalSelectionEnd, 10);
        /////// VARIANTS: 
        let variantStorage = document.getElementById('variantStorageBox');
        let variantSpellings = variantStorage ? variantStorage.getElementsByClassName('writtenvariantvalue') : [];
        let foundVariants = [selectedText];
        for (let p = 0; p < variantSpellings.length; p++) {
          let val = variantSpellings[p].textContent;
          if (val && !foundVariants.includes(val)) {
            foundVariants.push(val);
          }
        }
        /////// PROPERTIES of a given entity: 
        let properties = {};
        const etType = document.getElementById('entityTypeSelector')?.value;
        let propertyBox = document.getElementById('propertyBox');
        let propertyInputs = propertyBox ? propertyBox.querySelectorAll('input, select, textarea') : [];
        propertyInputs.forEach(pair => {
          if (!pair.name && !pair.dataset.name) return;
          let pairName = pair.name || pair.dataset.name;
          let pairValue = (pair.type === 'checkbox') ? pair.checked : pair.value;
          properties[pairName] = pairValue;
        });

        //appending to dataObject
        dataObject['token'] = token;
        dataObject['texid'] = (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.nodeid)
          ? languageOptions.nodeid
          : (window.currentTextNeoId || 0);
        dataObject['nodetype'] = etType;

        let annotationCreationDiv = document.getElementById('annotationCreationDiv');
        let annotationProperties = annotationCreationDiv ? annotationCreationDiv.getElementsByClassName('property') : [];
        let annotationCollectionBox = (typeof extractAnnotationPropertiesFromDOM === 'function' && annotationProperties.length)
          ? extractAnnotationPropertiesFromDOM(annotationProperties)
          : {};
        annotationCollectionBox[startcode] = startOfSelection;
        annotationCollectionBox[stopcode] = endOfSelection;

        dataObject['annotation'] = annotationCollectionBox;
        dataObject['variants'] = foundVariants;
        dataObject['properties'] = properties;    // == entity
        dataObject['wikidata_qid'] = (typeof chosenQID !== 'undefined' && chosenQID !== null && chosenQID !== -1 && /^Q[0-9]+$/.test(String(chosenQID).trim()))
          ? String(chosenQID).trim()
          : '';
        dataObject['split_wikidata_entity'] = document.getElementById('splitWikidataEntityCheckbox')?.checked ? 'true' : 'false';
        dataObject['annotationmode'] = datamode || 'automated';
        dataObject['neo_id_internal'] = (typeof auto_annotation_internal_id !== 'undefined' && !isNaN(auto_annotation_internal_id)) ? auto_annotation_internal_id : 0;

        //send dataobject to backend: 
        const formData = new FormData();
        function appendNested(obj, prefix) {
          for (const [k, v] of Object.entries(obj)) {
            const key = prefix ? `${prefix}[${k}]` : k;
            if (v !== null && typeof v === 'object' && !(v instanceof File)) {
              appendNested(v, key);
            } else {
              formData.append(key, v !== null && v !== undefined ? v : '');
            }
          }
        }
        appendNested(dataObject, 'data');

        fetch("/AJAX/put_annotation.php", {
          method: "POST",
          body: formData
        })
          .then(res => res.json())
          .then(data => {
            if (data.msg && !data.data) {
              if (typeof showToast === 'function') {
                showToast(data.msg, 'error');
              } else {
                alert(data.msg);
              }
              document.getElementById('saveEtToDb')?.removeAttribute('disabled');
              return;
            }
            let put_rs = data['data']; 
            if (!put_rs) return;

            // Clean up automatic suggestion from client state
            const currentSugId = window.currentLatteSuggestionId;
            if (currentSugId && typeof unhighlightAutomaticSuggestions === 'function') {
              unhighlightAutomaticSuggestions([currentSugId]);
            }
            if (Array.isArray(window.automatic_annotations)) {
              window.automatic_annotations = window.automatic_annotations.filter(a => {
                const aId = a.annotation || a.id;
                if (currentSugId && aId === currentSugId) return false;
                const sStart = parseInt(a.start !== undefined ? a.start : a.startPos, 10);
                const sStop = parseInt(a.stop !== undefined ? a.stop : a.endPos, 10);
                const overlaps = !(sStop < put_rs['start'] || sStart > put_rs['stop']);
                return !overlaps;
              });
              if (typeof updateAutomaticAnnotationBadges === 'function') {
                updateAutomaticAnnotationBadges();
              }
            }

            // Update stored annotations client cache
            const stored = (typeof getStoredAnnotations === 'function') ? getStoredAnnotations() : (window.storedAnnotations || null);
            if (stored) {
              if (!stored.relations) stored.relations = {};
              stored.relations[put_rs['uuid']] = {
                start: put_rs['start'],
                stop: put_rs['stop'],
                type: put_rs['type']
              };
            }

            displayUpdatedText(put_rs['type'], put_rs['start'], put_rs['stop'], put_rs['uuid']); 
            loadAnnotationData(put_rs['uuid']);
            if (typeof showToast === 'function') {
              const msg = put_rs.merged_into_existing
                ? 'Linked as spelling variant of existing ' + put_rs['type'] + '.'
                : 'Promoted automatic annotation to ' + put_rs['type'] + '.';
              showToast(msg, 'success');
            }
          })
          .catch(err => {
            console.error('Error creating annotation:', err);
            let errorMessage = (err && err['ERR']) ? err['ERR'] : 'Error creating annotation'; 
            if (typeof showToast === 'function') {
              showToast(errorMessage, 'error');
            }
            document.getElementById('saveEtToDb')?.removeAttribute('disabled');
          })
          .finally(() => {
            auto_annotation_internal_id = NaN; 
            window.currentLatteSuggestionId = null;
          });
      }); 
      document.getElementById('saveEtToDb')?.setAttribute('disabled', true); // Prevents dual submission!
  } else {
    //OK
    //  ==> form data is not valid: types don't match config definitions.
    let shakeButton = document.getElementById('saveEtToDb');
    if (shakeButton) {
      shakeButton.classList.add('animate-shake');
      shakeButton.addEventListener("animationend", function () {
        shakeButton.classList.remove('animate-shake');
      }, false);
    }
  }
  //show request results.
}

function toggleSlide(dir = 0) {
  chosenQID = null;
  // 0 closes the sidepanel; 1 opens it. Better than the original .toggle() functions
  if (dir === 0) {
    unmark(); //when closing the side panel, always remove the markup!
    const suggestionBox = document.getElementById('suggestionOnSelect');
    if (suggestionBox) {
      suggestionBox.remove();
    }
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
    selected = selector ? selector.value : null; //dropdown value selected. 
  }
  if (!selected) return;

  //if a user made a mistake: remove the old formcontent
  let deleteOldBox = document.getElementsByClassName('generatedFieldsForFormBox');
  while (deleteOldBox.length > 0) {
    deleteOldBox[0].parentNode.removeChild(deleteOldBox[0]);
  }
  //load properties for selected: 
  let formBox = document.createElement('div');
  formBox.setAttribute('id', 'propertyBox');
  formBox.classList.add('w-full', 'generatedFieldsForFormBox', 'mt-3', 'space-y-3');
  let propertyPrompt = document.createElement('p');
  propertyPrompt.appendChild(document.createTextNode('2) Fill out entity properties:'));
  propertyPrompt.className = 'text-xs font-semibold text-slate-700 mt-2 mb-1';
  formBox.appendChild(propertyPrompt);

  fetch('/AJAX/get_structure.php?type=' + encodeURIComponent(selected))
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
          newFieldContainer.className = 'space-y-1 my-1.5';
          let newFieldLabel = document.createElement('label');
          newFieldLabel.className = 'block text-[11px] font-semibold text-slate-600';
          let newFieldInput = document.createElement('input');
          newFieldInput.classList.add('inputelement');
          newFieldLabel.appendChild(document.createTextNode(humanLabel + ': '));

          if (datatype === 'wikidata') {
            newFieldInput.setAttribute('data-datatype', 'wikidata');
            newFieldInput.setAttribute('placeholder', 'e.g. Q123');
            newFieldInput.setAttribute('title', 'Wikidata ID must start with Q followed by an integer (e.g. Q123)');
            newFieldInput.setAttribute('pattern', '^Q[0-9]+$');
            if (typeof chosenQID !== 'undefined' && chosenQID !== null && chosenQID !== -1) {
              newFieldInput.value = chosenQID;
            }
            // Allow optionally splitting an annotation into a separate entity even if the Q-ID already exists.
            const splitWrapper = document.createElement('label');
            splitWrapper.className = 'mt-1.5 flex items-center gap-2 text-[11px] text-slate-600 cursor-pointer';
            const splitCheckbox = document.createElement('input');
            splitCheckbox.type = 'checkbox';
            splitCheckbox.id = 'splitWikidataEntityCheckbox';
            splitCheckbox.className = 'rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
            const splitText = document.createElement('span');
            splitText.textContent = 'Create separate entity if this Wikidata ID already exists (instead of adding as a spelling variant)';
            splitWrapper.appendChild(splitCheckbox);
            splitWrapper.appendChild(splitText);
            newFieldContainer.appendChild(splitWrapper);
          } else if (globalSelectionText && (key === 'name' || key === 'label' || key === 'value' || key === 'variant' || key === 'title')) {
            newFieldInput.value = globalSelectionText;
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
          if(uniqueness && datatype !== 'wikidata'){
            newFieldInput.classList.add('validateAs_unique');
            newFieldInput.required = true; 
          } else if (uniqueness && datatype === 'wikidata') {
            newFieldInput.classList.add('validateAs_unique');
          }
          newFieldInput.classList.add('attachValidator');
          newFieldInput.classList.add('validateAs_' + datatype);
          newFieldInput.classList.add('w-full', 'px-3', 'py-1.5', 'border', 'border-slate-300', 'text-slate-800', 'rounded-lg', 'text-xs', 'focus:ring-2', 'focus:ring-emerald-500', 'focus:border-emerald-500');
          newFieldContainer.appendChild(newFieldLabel);
          newFieldContainer.appendChild(newFieldInput);
          formBox.appendChild(newFieldContainer);
        });

        if (selector && selector.parentElement) {
          selector.parentElement.appendChild(formBox);
        }

        validator = new Validator;
        validator.set_id_of_dropdown_element_for_entity("entityTypeSelector");
        validator.pickup();

        let saveNewEntry = document.createElement('button');
        saveNewEntry.setAttribute('id', 'saveEtToDb');
        saveNewEntry.type = 'button';
        saveNewEntry.className = 'w-full mt-3 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition cursor-pointer shadow-xs';
        saveNewEntry.innerHTML = '<i class="fas fa-check text-xs"></i><span>Save and create entity</span>';
        saveNewEntry.addEventListener('click', function () {
          saveNewDB();
        });
        formBox.appendChild(saveNewEntry);
      } else if (data['msg'] == 'failed'){
        if(data['datacode'] == 0 ){
          handleNoLogin();
        }
      }
    });
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
  const formData = new FormData();
  formData.append('sourceNeoID', neoid_et);
  formData.append('texNeoid', text_neo_id);
  formData.append('csrf', token);
  formData.append('start', selection_start);
  formData.append('stop', selection_end);
  formData.append('selection', selected_text);
  if (extra_properties && typeof extra_properties === 'object') {
    for (const [k, v] of Object.entries(extra_properties)) {
      formData.append(`properties[${k}]`, v);
    }
  }

  try {
    const response = await fetch("/AJAX/crud/connect.php", {
      method: "POST",
      body: formData
    });
    const repldata = await response.json();
    let repl = document.createElement('p');
    repl.appendChild(document.createTextNode(repldata['msg']));
    document.getElementById('etmain').appendChild(repl);
    let annotationStart = repldata['start'];
    let annotationEnd = repldata['stop'];
    let annotationUID = repldata['annotation'];
    let annotationForType = repldata['type'];
    loadAnnotationData(annotationUID);
    displayUpdatedText(annotationForType, annotationStart, annotationEnd, annotationUID);

    // Provide option to link all identical occurrences in document.
    let batchContainer = document.createElement('div');
    batchContainer.setAttribute('id', 'batchLinkOccurrencesContainer');
    batchContainer.className = 'mt-3 p-2 bg-blue-50 border border-blue-200 rounded text-center';

    let batchBtn = document.createElement('button');
    batchBtn.className = 'bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-3 rounded text-sm';
    batchBtn.textContent = 'Apply to all occurrences in document';

    let batchStatus = document.createElement('p');
    batchStatus.className = 'text-xs text-gray-600 mt-1';
    batchStatus.textContent = 'Propagate link to all identical unlinked spans across this text.';

    batchBtn.addEventListener('click', async function () {
      batchBtn.disabled = true;
      batchBtn.textContent = 'Linking occurrences...';
      try {
        const tokenRes = await fetch("/AJAX/getdisposabletoken.php?task=1");
        const freshToken = await tokenRes.json();
        const batchForm = new FormData();
        batchForm.append('sourceNeoID', neoid_et);
        batchForm.append('texNeoid', text_neo_id);
        batchForm.append('selection', selected_text);
        batchForm.append('csrf', freshToken);
        batchForm.append('matchCase', 'true');
        batchForm.append('wholeWord', 'true');

        const batchRes = await fetch("/AJAX/crud/connect_all_occurrences.php", {
          method: "POST",
          body: batchForm
        });
        const batchData = await batchRes.json();
        if (batchData.success && batchData.created_count > 0) {
          batchData.annotations.forEach(anno => {
            displayUpdatedText(anno.type, anno.start, anno.stop, anno.uuid);
          });
          batchContainer.innerHTML = `<span class="text-green-700 font-semibold text-sm">${batchData.msg}</span>`;
        } else if (batchData.success) {
          batchContainer.innerHTML = `<span class="text-gray-600 text-sm">${batchData.msg}</span>`;
        } else {
          batchContainer.innerHTML = `<span class="text-red-600 text-sm">${batchData.error || 'Failed to link occurrences.'}</span>`;
        }
      } catch (batchErr) {
        console.error("Batch linking error:", batchErr);
        batchContainer.innerHTML = `<span class="text-red-600 text-sm">Error linking occurrences.</span>`;
      }
    });

    batchContainer.appendChild(batchBtn);
    batchContainer.appendChild(batchStatus);
    const etMain = document.getElementById('etmain');
    if (etMain) {
      etMain.appendChild(batchContainer);
    }
  } catch (err) {
    console.error("Connect error:", err);
  } finally {
    const btnParent = document.getElementById('assignEtToSelectionParent');
    if (btnParent) {
      btnParent.remove();
    }
  }
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

  // Show co-occurrence badge if candidate appears in this document.
  if (etdata && etdata.context_boost) {
    let contextBadge = document.createElement('div');
    contextBadge.className = 'my-1 inline-block bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded font-semibold border border-emerald-300';
    let freq = etdata.local_doc_freq ? ` (${etdata.local_doc_freq}x in this document)` : ' (mentioned in this document)';
    contextBadge.textContent = 'Co-occurs in document' + freq;
    levbox.appendChild(contextBadge);
  }

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
  if (wikidataID && typeof wikidataID === 'string' && /^Q[0-9]+$/.test(wikidataID.trim())) {
    wd = new wikibaseEntry(wikidataID.trim(), wdProperties, 'slideover', 'qid');
    wd.getWikidata()
      .then(function(){wd.renderEntities(wikidataID.trim())})
      .catch(function(err){ console.warn('Could not load Wikidata entry for ' + wikidataID, err); });
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
        acceptLink.className = 'px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-2xs transition';
        rejectLink.className = 'px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 font-semibold text-xs shadow-2xs transition ml-2'; 
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

function createWDPromptBox(createNodeDiv, positionDiv, initialSearchText = null){
  // Creates the Wikidata prompting box where string-based matching is performed. 
  const embVarElem = document.getElementById('embeddedSpellingVariants');
  if (embVarElem) {
    embVarElem.classList.add('hidden');
  }

  var wikidataQLabel = document.createElement('div');
  wikidataQLabel.setAttribute('readonly', true);
  wikidataQLabel.setAttribute('id', 'chosenQID');

  var wikidataPromptMainbox = document.createElement('div');
  wikidataPromptMainbox.setAttribute('id', 'wdsearchpromptbox');
  wikidataPromptMainbox.className = 'w-full my-3 p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs space-y-3';

  // Header section with Wikidata icon and title.
  let headerDiv = document.createElement('div');
  headerDiv.className = 'flex items-center justify-between pb-2 border-b border-slate-100';
  headerDiv.innerHTML = `
    <div class="flex items-center gap-2">
      <img src="/images/wikidatawiki_small.png" alt="Wikidata" class="h-4.5 w-auto object-contain">
      <h4 class="text-xs font-bold text-slate-900">Wikidata entity lookup</h4>
    </div>
    <span class="text-[11px] font-medium text-slate-400">String matching</span>
  `;
  wikidataPromptMainbox.appendChild(headerDiv);

  // Dynamic language indicator with icon, language name, and auto-detection badge.
  let langContainer = document.createElement('div');
  langContainer.setAttribute('id', 'wdPromptLangContainer');
  wikidataPromptMainbox.appendChild(langContainer);
  if (typeof renderWDPromptLangIndicator === 'function') {
    renderWDPromptLangIndicator(langContainer);
  }

  // Full-width keyword input container.
  let wikidataRowBox = document.createElement('div');
  wikidataRowBox.className = 'w-full relative';

  var wikidataInputBox = document.createElement('input');
  wikidataInputBox.setAttribute('id', 'wikidataInputPrompter');
  wikidataInputBox.setAttribute('type', 'text');
  wikidataInputBox.setAttribute('placeholder', 'Enter entity name or keywords...');
  wikidataInputBox.className = 'w-full block px-3.5 py-2 text-xs md:text-sm bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl shadow-2xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition font-medium text-slate-800 box-border';
  wikidataInputBox.value = (initialSearchText !== null && initialSearchText !== undefined)
    ? initialSearchText
    : (typeof globalSelectionText !== 'undefined' ? globalSelectionText : '');

  wikidataInputBox.addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
      wdprompt(wikidataInputBox.value, 0);
    }
  }); 
  wikidataRowBox.appendChild(wikidataInputBox);
  wikidataPromptMainbox.appendChild(wikidataRowBox);

  // Action buttons row.
  let btnRow = document.createElement('div');
  btnRow.className = 'flex items-center gap-2 pt-1';

  var searchButtonForWDPrompt = document.createElement('button');
  searchButtonForWDPrompt.type = 'button';
  searchButtonForWDPrompt.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-2xs transition cursor-pointer';
  searchButtonForWDPrompt.innerHTML = '<i class="fas fa-search text-[10px]"></i> <span>Search</span>';
  searchButtonForWDPrompt.addEventListener('click', function () {
    wdprompt(wikidataInputBox.value, 0);
  });
  btnRow.appendChild(searchButtonForWDPrompt);

  if(globalLoginAvailable){
    var noWikidataId = document.createElement('button');
    noWikidataId.type = 'button';
    noWikidataId.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200 transition cursor-pointer ml-auto';
    noWikidataId.innerHTML = '<i class="fas fa-unlink text-[10px] text-slate-400"></i> <span>Don\'t link</span>';
    noWikidataId.addEventListener('click', function () {
      chosenQID = null;
      qid = -1; 
      wd = null; 
      acceptQID(-1);
    });
    btnRow.appendChild(noWikidataId);
  }
  wikidataPromptMainbox.appendChild(btnRow);

  var wikidataResultsBox = document.createElement('div');
  wikidataResultsBox.setAttribute('id', 'wdpromptBox');
  wikidataPromptMainbox.appendChild(wikidataQLabel);
  wikidataPromptMainbox.appendChild(wikidataResultsBox);

  // Add all boxes to the DOM: 
  if (positionDiv !== false && positionDiv) {
    createNodeDiv.appendChild(positionDiv);
  }
  if (typeof spellingVariantDOMReturn !== 'undefined' && spellingVariantDOMReturn && typeof spellingVariantDOMReturn.get_HTML_content === 'function') {
    createNodeDiv.appendChild(spellingVariantDOMReturn.get_HTML_content());
  }

  // Add a Wikidata prompt box and trigger search:
  createNodeDiv.appendChild(wikidataPromptMainbox);
  searchButtonForWDPrompt.click();
  return createNodeDiv; 
}
window.createWDPromptBox = createWDPromptBox;


function buildAnnotationCreationBox(include_promptbox = true) {
  const hasNonCore = (typeof window.hasNonCoreEntities !== 'undefined')
    ? Boolean(window.hasNonCoreEntities)
    : (typeof coreNodes !== 'undefined' && coreNodes.length > 0);

  if (!hasNonCore) {
    showNoEntityTypesWarningInOverlay(globalSelectionText || '', globalSelectionStart || 0, globalSelectionEnd || 0);
    return;
  }

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
  embeddedCreateDiv.classList.remove('hidden');
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
      validator.set_id_of_dropdown_element_for_entity("entityTypeSelector");
      validator.pickup(); 
    });


  annotationDiv.appendChild(topTex);
  annotationDiv.appendChild(topBox);
  //      customizable annoation information: 
  embeddedCreateDiv.appendChild(annotationDiv);
  //add code to create a node from selection!
  //append newly created Div to the DOM: 
  if (!targetOfInfo) {
    targetOfInfo = document.getElementById('slideoverDynamicContent');
  }
  if (targetOfInfo) {
    targetOfInfo.appendChild(createNodeDiv);
  }
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
  statsTarget.innerHTML = '';
  middleblock.appendChild(statsTarget);
  //    2.2:    variants: creates a div where the variants interaction is held. 
  const variantsTarget = document.createElement('div');
  variantsTarget.setAttribute('id', 'etVariantsTarget');
  variantsTarget.classList.add('text-gray-600', 'w-full', 'm-2', 'p-2', 'left-0');
  //variantsTarget.innerHTML = '<p>HAS VARS??</p>';
  //  2.2b:     URLS: creates a div where URL relations are held.
  const relationsTarget = document.createElement('div'); 
  relationsTarget.setAttribute('id', 'urlrelations'); 
  relationsTarget.className = 'border border-slate-200/90 rounded-xl p-3.5 bg-slate-50/50 mb-3 flex-grow';

  const relationsHeader = document.createElement('div');
  relationsHeader.className = 'flex items-center justify-between mb-2';
  const relationsTargetTitle = document.createElement('div'); 
  relationsTargetTitle.appendChild(createDivider('Knowledge bases: '));
  const triggerAddActionButton = document.createElement('button'); 
  triggerAddActionButton.appendChild(document.createTextNode('+ Add link')); 
  triggerAddActionButton.setAttribute('id', 'add_kb_relation'); 
  triggerAddActionButton.className = 'px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-2xs transition';

  relationsHeader.appendChild(relationsTargetTitle);
  relationsHeader.appendChild(triggerAddActionButton);

  const relationsTargetSub = document.createElement('div'); 
  relationsTargetSub.setAttribute('id', 'urlrelationscontainer'); 
  relationsTargetSub.className = 'flex flex-wrap items-center pt-2 border-t border-slate-200/80'; 

  relationsTarget.appendChild(relationsHeader); 
  relationsTarget.appendChild(relationsTargetSub); 

  // Do not show knowledge bases box while schema notice is active.
  const hasNonCore = (typeof window.hasNonCoreEntities !== 'undefined')
    ? Boolean(window.hasNonCoreEntities)
    : (typeof coreNodes !== 'undefined' && coreNodes.length > 0);
  if (!hasNonCore) {
    relationsTarget.classList.add('hidden');
    relationsTarget.style.setProperty('display', 'none', 'important');
  }

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
  const hasNonCore = (typeof window.hasNonCoreEntities !== 'undefined')
    ? Boolean(window.hasNonCoreEntities)
    : (typeof coreNodes !== 'undefined' && coreNodes.length > 0);

  if (!hasNonCore) {
    showNoEntityTypesWarningInOverlay(globalSelectionText || '', globalSelectionStart || 0, globalSelectionEnd || 0);
    return;
  }

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
    let hasLevenshtein = Object.keys(levscores).length > 0; 

    console.log('SCORINGSTUFF'); 
    console.log(levscores); 
    console.log(hasLevenshtein); 
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
    if (hasLevenshtein){
        //APPLY sort only based on levenshtein distance if levenshtein values are known: 
        //Lowest LD is the node matching closest to what's asked; sort LD ASC. 
        // Function to sort the datadictionary based on ldist and document context.
        function sortDataDictionary(ldist, datadictionary) {
          return datadictionary.sort((a, b) => {
            const aBoost = a.context_boost ? 1 : 0;
            const bBoost = b.context_boost ? 1 : 0;
            if (aBoost !== bBoost) {
              return bBoost - aBoost;
            }

            const aId = a[0]; // Get the ID from the first element of the sub-array
            const bId = b[0]; // Get the ID from the first element of the sub-array

            const aValue = ldist[aId]; // Get the corresponding value from ldist
            const bValue = ldist[bId]; // Get the corresponding value from ldist

            // If aValue or bValue is undefined, treat it as a high value (for sorting).
            return (aValue || Infinity) - (bValue || Infinity);
          });
        }
        // store the result back to dataDictionary: Is now sorted
        // with ascending LD (lowest LD is closest match)
        dataDictionary = sortDataDictionary(levscores, dataDictionary);
    }


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

function rejectLatteSuggestion(segmentId) {
  // Find span boundary offsets and text.
  let bounds = findItercounterRange(segmentId);
  let start = bounds['lowest'];
  let stop = bounds['highest'];
  let text = bounds['text'];
  disableButtonByElemId('suggestionbox_saveButton');
  disableButtonByElemId('suggestionbox_dropButton');
  let rejectBtn = document.getElementById('suggestionbox_rejectButton');
  if (rejectBtn) {
    rejectBtn.disabled = true;
    rejectBtn.classList.add('disabled');
  }

  if (start > -1 && stop >= start) {
    fetch("/AJAX/getdisposabletoken.php?task=1")
      .then(response => response.json())
      .then(token => {
        let formData = new FormData();
        formData.append('texid', languageOptions.nodeid);
        formData.append('start', start);
        formData.append('stop', stop);
        formData.append('text', text);
        formData.append('csrf', token);

        fetch("/AJAX/reject_suggestion.php", {
          method: "POST",
          body: formData
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              dropLatteSuggestion(segmentId);
              if (typeof rejected_annotations !== 'undefined' && Array.isArray(rejected_annotations)) {
                rejected_annotations.push({
                  start: start,
                  stop: stop,
                  text: text
                });
              }
            } else {
              alert(data.error || 'Failed to reject suggestion.');
            }
          })
          .catch(err => {
            console.error('Error rejecting suggestion:', err);
            dropLatteSuggestion(segmentId);
          });
      })
      .catch(err => {
        console.error('Error fetching token for rejection:', err);
      });
  } else {
    dropLatteSuggestion(segmentId);
  }
}

function makeBoxTemplate(targetElement){
  /**
   * Creates a popup modal which can be called by other functions to customize further. 
   */
    if (!targetElement) {
      if (typeof event !== 'undefined' && event) {
        targetElement = event.srcElement || event.target;
      }
    }
    let mode = 'stored';
    let boxHeader = 'Entities';
    let headerColor = 'bg-teal-50 text-teal-800 border border-teal-200';
    let autoSug = null;
    let autoSugId = null;

    let anchorSpan = window.lastSelectedSpan;
    if (!anchorSpan) {
      const sel = window.getSelection();
      if (sel && sel.anchorNode) {
        const node = sel.anchorNode;
        anchorSpan = (node.nodeType === Node.TEXT_NODE ? node.parentElement : node).closest('.ltr');
      }
    }
    if (!anchorSpan && targetElement && targetElement.closest) {
      anchorSpan = targetElement.closest('.ltr');
    }

    // Check if targetElement or anchorSpan or selection corresponds to an automatic suggestion
    if (targetElement && targetElement.getAttribute) {
      const raw = targetElement.getAttribute('data-annotation');
      if (raw) {
        const ids = raw.split(',');
        autoSugId = ids.find(id => id.startsWith('latte_sug_') || (Array.isArray(window.automatic_annotations) && window.automatic_annotations.some(a => (a.annotation === id || a.id === id))));
      }
    }
    if (!autoSugId && anchorSpan && anchorSpan.getAttribute) {
      const raw = anchorSpan.getAttribute('data-annotation');
      if (raw) {
        const ids = raw.split(',');
        autoSugId = ids.find(id => id.startsWith('latte_sug_') || (Array.isArray(window.automatic_annotations) && window.automatic_annotations.some(a => (a.annotation === id || a.id === id))));
      }
    }
    if (autoSugId && Array.isArray(window.automatic_annotations)) {
      autoSug = window.automatic_annotations.find(a => (a.annotation === autoSugId || a.id === autoSugId));
    }
    if (!autoSug && Array.isArray(window.automatic_annotations) && window.lastSelectedStart !== undefined && window.lastSelectedEnd !== undefined) {
      autoSug = window.automatic_annotations.find(a => {
        const s = parseInt(a.start !== undefined ? a.start : a.startPos, 10);
        const e = parseInt(a.stop !== undefined ? a.stop : a.endPos, 10);
        return (window.lastSelectedStart >= s && window.lastSelectedEnd <= e);
      });
      if (autoSug) {
        autoSugId = autoSug.annotation || autoSug.id;
      }
    }

    if (autoSug || autoSugId) {
      boxHeader = (autoSug && autoSug.label) ? ('LATTE suggestion: ' + autoSug.label) : 'Automatic suggestion';
      headerColor = 'bg-slate-100 text-slate-800 border border-slate-300';
      mode = 'automatic';
    } else if(targetElement && targetElement.classList && targetElement.classList.contains('automatic_unstored')){
      boxHeader = 'Unstored Annotation';
      headerColor = 'bg-indigo-50 text-indigo-800 border border-indigo-200'; 
      mode = 'unstored';
    }
    ignoreSuggestion();

    let topDst = 100;
    let height = 20;
    let leftDst = 150;
    if (anchorSpan) {
      const rect = anchorSpan.getBoundingClientRect();
      const scrollX = window.scrollX || window.pageXOffset || 0;
      const scrollY = window.scrollY || window.pageYOffset || 0;
      topDst = rect.top + scrollY;
      height = rect.height;
      leftDst = rect.left + scrollX - 100;
      if (leftDst < 16) {
        leftDst = 16;
      }
      if (leftDst + 320 > window.innerWidth) {
        leftDst = Math.max(16, window.innerWidth - 330);
      }
    }

    //create div at fixed position: THIS IS ALWAYS REQUIRED
    var div = document.createElement('div');
    div.dataset.mode = mode;
    if (autoSug || autoSugId) {
      div.dataset.autoSugId = autoSugId || (autoSug ? (autoSug.annotation || autoSug.id) : '');
      div.dataset.autoSugStart = (autoSug && autoSug.start !== undefined) ? autoSug.start : (window.lastSelectedStart || 0);
      div.dataset.autoSugStop = (autoSug && autoSug.stop !== undefined) ? autoSug.stop : (window.lastSelectedEnd || 0);
      div.dataset.autoSugLabel = (autoSug && autoSug.label) ? autoSug.label : 'Entity';
      div.dataset.autoSugText = (autoSug && autoSug.text) ? autoSug.text : (window.lastSelectedText || '');
    }

    var tex = document.createTextNode(boxHeader);
    var texheader = document.createElement('h3');
    texheader.appendChild(tex);
    texheader.className = headerColor + ' text-center font-bold text-xs py-1 px-2.5 rounded-lg mb-2 shadow-2xs';
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
  var [div, mode, topDst, height, leftDst] = makeBoxTemplate(); 
  const hasNonCore = (typeof window.hasNonCoreEntities !== 'undefined')
    ? Boolean(window.hasNonCoreEntities)
    : (typeof coreNodes !== 'undefined' && coreNodes.length > 0);

  // If automatic suggestion, render the Retrim and Delete controls
  if (mode === 'automatic') {
    const sugId = div.dataset.autoSugId || '';
    const sugStart = parseInt(div.dataset.autoSugStart || '0', 10);
    const sugStop = parseInt(div.dataset.autoSugStop || '0', 10);
    const sugLabel = div.dataset.autoSugLabel || 'Entity';
    const sugText = div.dataset.autoSugText || (window.lastSelectedText || '');
    const escHtml = (typeof window.escapeHtml === 'function') ? window.escapeHtml : (s => s);

    const autoBox = document.createElement('div');
    autoBox.className = 'px-3 py-2 space-y-2 border-b border-slate-100';
    autoBox.innerHTML = `
      <div class="flex items-center justify-between text-[11px]">
        <span class="font-bold text-slate-800 truncate max-w-[170px]" title="${escHtml(sugText)}">"${escHtml(sugText)}"</span>
        <span class="text-slate-400 font-mono text-[10px]">${sugStart} - ${sugStop}</span>
      </div>
      <div class="flex items-center gap-2 pt-1">
        ${hasNonCore ? `
        <button type="button" id="suggestionbox_promoteButton" class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition cursor-pointer shadow-2xs" title="Promote to custom entity">
          <i class="fas fa-arrow-up text-[10px]"></i>
          <span>Promote</span>
        </button>
        ` : ''}
        <button type="button" id="suggestionbox_retrimButton" class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition cursor-pointer shadow-2xs" title="Adjust the boundary handles of this selection">
          <i class="fas fa-arrows-alt-h text-[10px]"></i>
          <span>Retrim</span>
        </button>
        <button type="button" id="suggestionbox_deleteButton" class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition cursor-pointer shadow-2xs" title="Delete this automatic suggestion">
          <i class="fas fa-trash-alt text-[10px]"></i>
          <span>Delete</span>
        </button>
      </div>
    `;
    div.appendChild(autoBox);

    setTimeout(() => {
      document.getElementById('suggestionbox_promoteButton')?.addEventListener('click', function(e) {
        e.stopPropagation();
        ignoreSuggestion();
        if (typeof loadAnnotationData === 'function') {
          loadAnnotationData(sugId);
        }
      });
      document.getElementById('suggestionbox_retrimButton')?.addEventListener('click', function(e) {
        e.stopPropagation();
        ignoreSuggestion();
        if (typeof startRetrimming === 'function') {
          startRetrimming(sugId, sugStart, sugStop, sugLabel);
        }
      });
      document.getElementById('suggestionbox_deleteButton')?.addEventListener('click', function(e) {
        e.stopPropagation();
        ignoreSuggestion();
        if (typeof removeAutomaticSuggestion === 'function') {
          removeAutomaticSuggestion(sugId);
        }
      });
    }, 0);
  }

  // Spinner is added if custom entity types can be linked
  if (hasNonCore && (mode === 'stored' || mode === 'automatic')) {
    var spinner = document.createElement('div');
    spinner.innerHTML = '<div id="suggestionboxspinner" class="text-center my-3 p-2"> <svg role="status" class="inline w-6 h-6 text-slate-200 animate-spin fill-indigo-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/> <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/> </svg> </div>';
    div.appendChild(spinner);
  } else if (!hasNonCore && mode === 'stored') {
    var note = document.createElement('div');
    note.className = 'px-3 py-2 text-center text-[11px] text-slate-500';
    note.textContent = 'Custom entity types must be defined in the schema to create annotations.';
    div.appendChild(note);
  }

  var targetElement = (typeof event !== 'undefined' && event) ? (event.src || event.target) : null;

  div.classList.add('suggestionBox', 'bg-white');
  div.style.position = 'absolute';
  div.style.top = topDst + height + 6 + 'px';
  div.style.left = leftDst + 'px';
  div.style.minWidth = '260px';
  div.style.maxWidth = '320px';
  div.setAttribute('id', 'suggestionOnSelect');
  //save/dismiss button:
  var buttonsBottom = document.createElement('div');
  var dismiss = document.createElement('button');
  dismiss.addEventListener('click', function () {
    ignoreSuggestion();
  });
  var dismisstext = document.createTextNode('Dismiss');
  buttonsBottom.className = 'w-full mt-3 pt-2.5 border-t border-slate-100 flex items-center gap-1.5 flex-wrap';
  dismiss.className = 'px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex-1 text-center';
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
    var droptext = document.createTextNode('Reannotate'); 
    drop.appendChild(droptext); 
    drop.className = 'px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition flex-1 text-center disabled:opacity-50 disabled:cursor-not-allowed'; 
    buttonsBottom.appendChild(drop); 
  }
  if (mode === 'unstored' && globalLoginAvailable){
    var save = document.createElement('button');
    save.addEventListener('click', function(){
      persistSuggestionOfLatteConnector(targetElement.dataset.segment_id);
    });
    var savetext = document.createTextNode('Store');
    save.className = 'px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-2xs transition flex-1 text-center disabled:opacity-50 disabled:cursor-not-allowed';
    save.setAttribute('id', 'suggestionbox_saveButton');
    save.appendChild(savetext);
    buttonsBottom.appendChild(save);

    if (targetElement.classList.contains('automatic_unstored')) {
      var rejectBtn = document.createElement('button');
      rejectBtn.setAttribute('id', 'suggestionbox_rejectButton');
      rejectBtn.addEventListener('click', function(){
        rejectLatteSuggestion(targetElement.dataset.segment_id);
      });
      var rejectText = document.createTextNode('Reject');
      rejectBtn.className = 'px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition flex-1 text-center disabled:opacity-50 disabled:cursor-not-allowed';
      rejectBtn.appendChild(rejectText);
      buttonsBottom.appendChild(rejectBtn);
    }
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
  // Only trigger side panel create action for raw unannotated text selections.
  // Do not clobber existing annotation inspector when viewing automated or stored annotations.
  if (datamode !== 'automated' && datamode !== 'controll' && datamode !== 'stored') {
    triggerSidePanelAction(data);
  }
  datadiv.appendChild(dataOnSearch);
  const spinnerEl = document.getElementById('suggestionboxspinner');
  if (datamode === 'stored' || datamode === 'automated') {
    if (spinnerEl && spinnerEl.parentNode) {
      spinnerEl.parentNode.insertBefore(datadiv, spinnerEl);
      spinnerEl.remove();
    }
    if (typeof window.renderMatchingEntitiesInSuggestionBox === 'function') {
      const selSnippet = (typeof getSelectedTextSnippet === 'function')
        ? getSelectedTextSnippet(from, to)
        : (typeof globalSelectionText !== 'undefined' ? globalSelectionText : '');
      window.renderMatchingEntitiesInSuggestionBox(data, from, to, (selSnippet || '').trim());
    }
  } else {
    if (spinnerEl) {
      spinnerEl.remove();
    }
    const suggestionBox = document.getElementById('suggestionOnSelect');
    if (suggestionBox) {
      suggestionBox.remove();
    }
  }
  handleKbs(data['silo']); 
}

window.mouseDragStartIter = null;
window.mouseDragEndIter = null;
let isMouseSelecting = false;

function getTextSelection() {
  const sel = window.getSelection();
  let startIter = null;
  let endIter = null;

  if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
    const range = sel.getRangeAt(0);
    const textContent = document.getElementById('textcontent');
    if (textContent && textContent.contains(range.commonAncestorContainer)) {
      let startNode = range.startContainer;
      let startSpan = (startNode.nodeType === Node.TEXT_NODE ? startNode.parentElement : startNode).closest('.ltr');

      let endNode = range.endContainer;
      let endSpan = (endNode.nodeType === Node.TEXT_NODE ? endNode.parentElement : endNode).closest('.ltr');

      if (startSpan && startSpan.dataset.itercounter !== undefined) {
        startIter = parseInt(startSpan.dataset.itercounter, 10);
      }
      if (endSpan && endSpan.dataset.itercounter !== undefined) {
        endIter = parseInt(endSpan.dataset.itercounter, 10);
        if (range.endOffset === 0 && endIter > startIter) {
          endIter = endIter - 1;
        }
      }
    }
  }

  // Fallback to mouse drag range if selection was collapsed or did not resolve spans
  if (startIter === null || endIter === null) {
    if (window.mouseDragStartIter !== null && window.mouseDragEndIter !== null) {
      startIter = window.mouseDragStartIter;
      endIter = window.mouseDragEndIter;
    }
  }

  if (startIter === null || endIter === null) {
    return false;
  }

  // If a single span was clicked without dragging, expand to suggestion or word boundaries
  if (startIter === endIter && (!sel || sel.isCollapsed)) {
    const clickedSpan = (typeof getSpanByIter === 'function') ? getSpanByIter(startIter) : document.querySelector(`.ltr[data-itercounter="${startIter}"]`);
    if (clickedSpan) {
      // Check if clicked span is part of an automatic annotation
      let matchedSug = null;
      const autoId = clickedSpan.getAttribute('data-annotation');
      if (autoId && Array.isArray(window.automatic_annotations)) {
        matchedSug = window.automatic_annotations.find(a => (a.annotation === autoId || (autoId.split(',').includes(a.annotation || a.id))));
      }
      if (!matchedSug && Array.isArray(window.automatic_annotations)) {
        matchedSug = window.automatic_annotations.find(a => {
          const s = parseInt(a.start !== undefined ? a.start : a.startPos, 10);
          const e = parseInt(a.stop !== undefined ? a.stop : a.endPos, 10);
          return (startIter >= s && startIter <= e);
        });
      }

      if (matchedSug) {
        startIter = parseInt(matchedSug.start !== undefined ? matchedSug.start : matchedSug.startPos, 10);
        endIter = parseInt(matchedSug.stop !== undefined ? matchedSug.stop : matchedSug.endPos, 10);
      } else if (clickedSpan.textContent.trim().length > 0) {
        let wordStart = startIter;
        let wordEnd = endIter;
        while (wordStart > 0) {
          const prevSpan = (typeof getSpanByIter === 'function') ? getSpanByIter(wordStart - 1) : document.querySelector(`.ltr[data-itercounter="${wordStart - 1}"]`);
          if (!prevSpan || prevSpan.textContent.trim().length === 0 || /\s/.test(prevSpan.textContent)) {
            break;
          }
          wordStart--;
        }
        while (true) {
          const nextSpan = (typeof getSpanByIter === 'function') ? getSpanByIter(wordEnd + 1) : document.querySelector(`.ltr[data-itercounter="${wordEnd + 1}"]`);
          if (!nextSpan || nextSpan.textContent.trim().length === 0 || /\s/.test(nextSpan.textContent)) {
            break;
          }
          wordEnd++;
        }
        startIter = wordStart;
        endIter = wordEnd;
      }
    }
  }

  let minIter = Math.min(startIter, endIter);
  let maxIter = Math.max(startIter, endIter);

  // Extract raw text by concatenating the exact indexed spans to guarantee 1:1 character index parity
  let rawText = '';
  for (let i = minIter; i <= maxIter; i++) {
    const span = (typeof getSpanByIter === 'function') ? getSpanByIter(i) : document.querySelector(`.ltr[data-itercounter="${i}"]`);
    if (span) {
      rawText += span.textContent;
    }
  }

  if (rawText.length === 0) {
    return false;
  }

  // Trim whitespace while adjusting start and end counters exactly as original
  let trimmedText = rawText.trim();
  if (trimmedText !== rawText) {
    while (!rawText.startsWith(trimmedText) && minIter < maxIter) {
      minIter += 1;
      rawText = rawText.slice(1);
    }
    while (!rawText.endsWith(trimmedText) && maxIter > minIter) {
      maxIter -= 1;
      rawText = rawText.slice(0, rawText.length - 1);
    }
  }

  globalSelectionText = rawText;
  globalSelectionStart = minIter;
  globalSelectionEnd = maxIter;

  window.lastSelectedSpan = (typeof getSpanByIter === 'function') ? (getSpanByIter(maxIter) || getSpanByIter(minIter)) : null;

  return [globalSelectionText, globalSelectionStart, globalSelectionEnd];
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

function escapeHtml(str) {
  if (typeof str !== 'string') return '';
  return str.replace(/[&<>"']/g, function (m) {
    switch (m) {
      case '&': return '&amp;';
      case '<': return '&lt;';
      case '>': return '&gt;';
      case '"': return '&quot;';
      case "'": return '&#39;';
      default: return m;
    }
  });
}
window.escapeHtml = escapeHtml;

function showToast(message, type = 'error', duration = 5000) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.setAttribute('id', 'toastContainer');
    container.className = 'fixed top-24 left-8 z-[99999] flex flex-col items-start gap-2 pointer-events-none';
    container.style.cssText = 'top: 5.5rem !important; left: 2rem !important; right: auto !important; width: auto !important; max-width: min(32rem, calc(100vw - 4rem)) !important; z-index: 99999 !important;';
    document.body.appendChild(container);
  } else {
    container.style.cssText = 'top: 5.5rem !important; left: 2rem !important; right: auto !important; width: auto !important; max-width: min(32rem, calc(100vw - 4rem)) !important; z-index: 99999 !important;';
  }

  const toast = document.createElement('div');
  const isErr = (type === 'error');
  toast.className = 'toast-notification ' + (isErr ? 'toast-error bg-red-600 text-white' : 'toast-success bg-emerald-600 text-white') + ' transform translate-y-2 opacity-0';
  toast.style.cssText = isErr
    ? 'background-color: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c !important; width: auto !important; max-width: min(32rem, calc(100vw - 4rem)) !important; display: inline-flex !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.35) !important;'
    : 'background-color: #059669 !important; color: #ffffff !important; border: 1px solid #047857 !important; width: auto !important; max-width: min(32rem, calc(100vw - 4rem)) !important; display: inline-flex !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.35) !important;';

  const icon = document.createElement('div');
  icon.className = 'flex items-center justify-center shrink-0 text-base';
  icon.style.cssText = 'color: #ffffff !important;';
  icon.innerHTML = isErr
    ? '<i class="fas fa-exclamation-circle"></i>'
    : '<i class="fas fa-check-circle"></i>';

  const textDiv = document.createElement('div');
  textDiv.className = 'leading-snug';
  textDiv.style.cssText = 'color: #ffffff !important; font-weight: 600 !important; font-size: 0.8125rem !important;';
  textDiv.textContent = message;

  const closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'transition shrink-0 ml-2 p-0.5';
  closeBtn.style.cssText = 'color: #ffffff !important; opacity: 0.9; cursor: pointer;';
  closeBtn.innerHTML = '<i class="fas fa-times text-xs"></i>';
  closeBtn.onclick = () => {
    toast.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => toast.remove(), 300);
  };

  toast.appendChild(icon);
  toast.appendChild(textDiv);
  toast.appendChild(closeBtn);
  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.remove('opacity-0', 'translate-y-2');
  });

  if (duration > 0) {
    setTimeout(() => {
      if (toast.parentElement) {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
      }
    }, duration);
  }
}
window.showToast = showToast;

function showNoEntityTypesWarningInOverlay(selectedText, start, end) {
  toggleSlide(1);
  const titleEl = document.getElementById('slideover-title');
  if (titleEl) {
    titleEl.textContent = 'Entity & annotation inspector';
  }
  const content = document.getElementById('slideoverDynamicContent');
  if (!content) return;

  const isAdmin = (typeof window.userIsAdmin !== 'undefined') ? window.userIsAdmin : false;

  content.innerHTML = `
    <div class="space-y-6">
      <div class="p-5 rounded-2xl border space-y-3 shadow-xs" style="background-color: #fef2f2 !important; border-color: #fecdd3 !important; color: #991b1b !important;">
        <div class="flex items-start gap-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5" style="background-color: #fee2e2 !important; color: #dc2626 !important;">
            <i class="fas fa-exclamation-triangle text-base"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold leading-snug" style="color: #991b1b !important;">
              No entity types outside of the core components have been defined.
            </h3>
            <p class="text-xs mt-1.5 leading-relaxed" style="color: #b91c1c !important;">
              Before you can create annotations or classify text passages, at least one custom entity type (such as Person, Place, or Organization) must be defined in the schema. Only the mandatory architecture components (Text, Annotation, and Variant) currently exist.
            </p>
          </div>
        </div>
        ${isAdmin ? `
        <div class="pt-3 border-t flex items-center justify-between" style="border-color: #fee2e2 !important;">
          <span class="text-xs" style="color: #b91c1c !important;">Configure your schema to get started.</span>
          <a href="/admin/entities.php" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-white text-xs font-semibold shadow-xs transition-colors" style="background-color: #dc2626 !important;">
            <i class="fas fa-layer-group text-[11px]"></i>
            <span>Configure entity types</span>
          </a>
        </div>
        ` : `
        <div class="pt-2 border-t text-xs" style="border-color: #fee2e2 !important; color: #b91c1c !important;">
          Please ask an administrator to add entity types in the entity configuration section.
        </div>
        `}
      </div>

      <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
        <div class="flex items-center justify-between text-xs text-slate-500">
          <span class="font-semibold text-slate-700">Selected text</span>
          <span class="font-mono">Character offsets: ${start} - ${end}</span>
        </div>
        <div class="font-mono text-xs bg-white p-3 rounded-lg border border-slate-200 text-slate-800 break-words max-h-32 overflow-y-auto">
          ${escapeHtml(selectedText)}
        </div>
      </div>
    </div>
  `;
}
window.showNoEntityTypesWarningInOverlay = showNoEntityTypesWarningInOverlay;

function triggerSelection() {
  if (window.isRetrimmingActive) {
    return;
  }
  unmark();
  var selectedTextProperties = getTextSelection();
  var selectedText = selectedTextProperties[0];
  var selectedTextStart = selectedTextProperties[1];
  var selectedTextEnd = selectedTextProperties[2];

  if (selectedText) {
    const hasNonCore = (typeof window.hasNonCoreEntities !== 'undefined')
      ? Boolean(window.hasNonCoreEntities)
      : (typeof coreNodes !== 'undefined' && coreNodes.length > 0);

    // Detect if the clicked selection is an automated suggestion
    const startSpan = (typeof getSpanByIter === 'function')
      ? getSpanByIter(selectedTextStart)
      : document.querySelector(`.ltr[data-itercounter="${selectedTextStart}"]`);
    const isAuto = Boolean(startSpan && (
      startSpan.classList.contains('app_automatic') ||
      (startSpan.getAttribute('data-annotation') || '').includes('latte_sug_') ||
      (Array.isArray(window.automatic_annotations) && window.automatic_annotations.some(
        a => selectedTextStart >= parseInt(a.start !== undefined ? a.start : a.startPos, 10) &&
             selectedTextEnd <= parseInt(a.stop !== undefined ? a.stop : a.endPos, 10)
      ))
    ));

    if (isAuto) {
      datamode = 'automated';
    } else {
      datamode = null;
    }

    if (!hasNonCore && !isAuto) {
      showToast("No entity types outside of the core components have been defined.", "error");
    }

    // Trigger when selecting text.
    makeSuggestionBox();

    // Only query backend entity suggestions if custom entity types exist
    if (hasNonCore) {
      const useLd = document.getElementById('use_ld');
      const maxLd = document.getElementById('max_ld');
      const params = new URLSearchParams({
        'type': '',
        'value': selectedText.trim(),
        'casesensitive': 'false',
        'allow_levenshtein': (useLd && useLd.checked) ? 'true' : 'false',
        'levenshtein_items': (maxLd && maxLd.value) ? maxLd.value : '5',
        'texid': (typeof languageOptions !== 'undefined' && languageOptions && languageOptions['nodeid']) ? languageOptions['nodeid'] : ''
      });
      const sendTo = '/AJAX/getEntitySuggestion.php?' + params.toString();
      getInfoFromBackend(sendTo)
        .then((data) => {
          loadIntoSuggestionBox(data, selectedTextStart, selectedTextEnd);
        });
    }
  }
}

window.quickLinkSpanToEntity = async function (entityNeoId, startPos, stopPos, textSnippet, propagateAll = false) {
  const texNeoId = (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.nodeid)
    ? languageOptions.nodeid
    : (window.currentTextNeoId || 0);
  if (!texNeoId || !entityNeoId) return;

  const tokenRes = await fetch('/user/AJAX/profilestate.php?fastconnect=1');
  const profileData = await tokenRes.json();
  const token = (profileData && profileData.csrf) ? profileData.csrf : '';

  const formData = new FormData();
  formData.append('sourceNeoID', entityNeoId);
  formData.append('texNeoid', texNeoId);
  formData.append('csrf', token);
  formData.append('start', startPos);
  formData.append('stop', stopPos);
  formData.append('selection', textSnippet);

  const response = await fetch('/AJAX/crud/connect.php', {
    method: 'POST',
    body: formData
  });
  const repldata = await response.json();
  if (!repldata || typeof repldata !== 'object' || !repldata.annotation) {
    const errMsg = (typeof repldata === 'string') ? repldata : ((repldata && (repldata.msg || repldata.error)) ? (repldata.msg || repldata.error) : 'Failed to link entity.');
    if (typeof showToast === 'function') {
      showToast(errMsg, 'error');
    }
    throw new Error(errMsg);
  }

  // Promote any overlapping automatic suggestion from client state.
  if (Array.isArray(window.automatic_annotations)) {
    window.automatic_annotations = window.automatic_annotations.filter(a => {
      const sStart = parseInt(a.start !== undefined ? a.start : a.startPos, 10);
      const sStop = parseInt(a.stop !== undefined ? a.stop : a.endPos, 10);
      return (sStop < repldata.start || sStart > repldata.stop);
    });
    if (typeof updateAutomaticAnnotationBadges === 'function') {
      updateAutomaticAnnotationBadges();
    }
  }

  if (window.storedAnnotations && window.storedAnnotations.relations) {
    window.storedAnnotations.relations[repldata.annotation] = {
      start: repldata.start,
      stop: repldata.stop,
      type: repldata.type
    };
  }
  displayUpdatedText(repldata.type, repldata.start, repldata.stop, repldata.annotation);

  let propagatedCount = 0;
  if (propagateAll && textSnippet) {
    const tokenRes2 = await fetch('/AJAX/getdisposabletoken.php?task=1');
    const token2 = await tokenRes2.json();
    const batchForm = new FormData();
    batchForm.append('sourceNeoID', entityNeoId);
    batchForm.append('texNeoid', texNeoId);
    batchForm.append('selection', textSnippet);
    batchForm.append('csrf', token2);
    batchForm.append('matchCase', 'true');
    batchForm.append('wholeWord', 'true');

    const batchRes = await fetch('/AJAX/crud/connect_all_occurrences.php', {
      method: 'POST',
      body: batchForm
    });
    const batchData = await batchRes.json();
    if (batchData && batchData.success && Array.isArray(batchData.annotations)) {
      propagatedCount = batchData.created_count || 0;
      batchData.annotations.forEach(anno => {
        if (Array.isArray(window.automatic_annotations)) {
          window.automatic_annotations = window.automatic_annotations.filter(a => {
            const sStart = parseInt(a.start !== undefined ? a.start : a.startPos, 10);
            const sStop = parseInt(a.stop !== undefined ? a.stop : a.endPos, 10);
            return (sStop < anno.start || sStart > anno.stop);
          });
        }
        if (window.storedAnnotations && window.storedAnnotations.relations) {
          window.storedAnnotations.relations[anno.uuid] = {
            start: anno.start,
            stop: anno.stop,
            type: anno.type
          };
        }
        displayUpdatedText(anno.type, anno.start, anno.stop, anno.uuid);
      });
      if (typeof updateAutomaticAnnotationBadges === 'function') {
        updateAutomaticAnnotationBadges();
      }
    }
  }

  if (typeof showToast === 'function') {
    const msg = propagateAll
      ? `Linked to ${repldata.type} and propagated to ${propagatedCount} additional occurrence(s).`
      : `Linked selection to ${repldata.type}.`;
    showToast(msg, 'success');
  }

  if (typeof loadAnnotationData === 'function') {
    loadAnnotationData(repldata.annotation);
  }
  const suggestionBox = document.getElementById('suggestionOnSelect');
  if (suggestionBox) {
    suggestionBox.remove();
  }
  return repldata;
};

window.renderMatchingEntitiesInSuggestionBox = function (data, from, to, textSnippet) {
  const box = document.getElementById('suggestionOnSelect');
  if (!box || !data || !Array.isArray(data.nodes) || data.nodes.length === 0) return;

  const existing = document.getElementById('quickEntityMatchSection');
  if (existing) existing.remove();

  const section = document.createElement('div');
  section.id = 'quickEntityMatchSection';
  section.className = 'mt-2.5 pt-2.5 border-t border-slate-100 space-y-1.5';

  const heading = document.createElement('div');
  heading.className = 'text-[11px] font-bold uppercase tracking-wider text-indigo-600 flex items-center gap-1';
  heading.innerHTML = '<i class="fas fa-link"></i><span>Matching entities</span>';
  section.appendChild(heading);

  const canEdit = (typeof window.userHasEditRights !== 'undefined' && window.userHasEditRights) || (typeof globalLoginAvailable !== 'undefined' && globalLoginAvailable);

  data.nodes.slice(0, 5).forEach(nodeItem => {
    const eid = nodeItem[0];
    const etype = nodeItem[1] || 'Entity';
    const props = nodeItem[2] || {};
    let displayLabel = '';
    for (const [k, v] of Object.entries(props)) {
      if (v && v.value && k !== 'uid') {
        displayLabel = String(v.value);
        break;
      }
    }
    if (!displayLabel) displayLabel = '#' + eid;

    const row = document.createElement('div');
    row.className = 'flex items-center justify-between gap-2 p-1.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs';

    const info = document.createElement('div');
    info.className = 'min-w-0 flex-1';
    info.innerHTML = `<span class="font-semibold text-slate-800 truncate block">${displayLabel.replace(/</g, '&lt;')}</span><span class="text-[10px] text-slate-500">${etype}</span>`;
    row.appendChild(info);

    if (canEdit) {
      const actions = document.createElement('div');
      actions.className = 'flex items-center gap-1 flex-shrink-0';

      const linkBtn = document.createElement('button');
      linkBtn.type = 'button';
      linkBtn.className = 'px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-semibold cursor-pointer';
      linkBtn.textContent = 'Link';
      linkBtn.onclick = function (e) {
        e.stopPropagation();
        linkBtn.disabled = true;
        window.quickLinkSpanToEntity(eid, from, to, textSnippet, false).catch(() => { linkBtn.disabled = false; });
      };

      const linkAllBtn = document.createElement('button');
      linkAllBtn.type = 'button';
      linkAllBtn.className = 'px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold cursor-pointer';
      linkAllBtn.textContent = 'Link all';
      linkAllBtn.title = 'Link this mention and all matching occurrences in document';
      linkAllBtn.onclick = function (e) {
        e.stopPropagation();
        linkAllBtn.disabled = true;
        window.quickLinkSpanToEntity(eid, from, to, textSnippet, true).catch(() => { linkAllBtn.disabled = false; });
      };

      actions.appendChild(linkBtn);
      actions.appendChild(linkAllBtn);
      row.appendChild(actions);
    }

    section.appendChild(row);
  });

  const dismissBtn = document.getElementById('suggestionbox_dismissButton');
  if (dismissBtn && dismissBtn.parentNode) {
    box.insertBefore(section, dismissBtn.parentNode);
  } else {
    box.appendChild(section);
  }
};

function initTextSelectionDelegation() {
  const textContainer = document.getElementById('textcontent');
  if (!textContainer || textContainer.dataset.selectionDelegationBound) {
    return;
  }
  textContainer.dataset.selectionDelegationBound = 'true';

  textContainer.addEventListener('mousedown', function (e) {
    if (window.isRetrimmingActive || e.target.closest('.latte-retrim-handle') || e.target.closest('#latteRetrimBar')) {
      return;
    }
    const span = e.target.closest('.ltr');
    if (span && span.dataset.itercounter !== undefined) {
      isMouseSelecting = true;
      window.mouseDragStartIter = parseInt(span.dataset.itercounter, 10);
      window.mouseDragEndIter = window.mouseDragStartIter;
    } else {
      isMouseSelecting = false;
      window.mouseDragStartIter = null;
      window.mouseDragEndIter = null;
    }
  });

  textContainer.addEventListener('mousemove', function (e) {
    if (!isMouseSelecting) return;
    const span = e.target.closest('.ltr');
    if (span && span.dataset.itercounter !== undefined) {
      window.mouseDragEndIter = parseInt(span.dataset.itercounter, 10);
    }
  });

  textContainer.addEventListener('mouseup', function (e) {
    if (isMouseSelecting) {
      const span = e.target.closest('.ltr');
      if (span && span.dataset.itercounter !== undefined) {
        window.mouseDragEndIter = parseInt(span.dataset.itercounter, 10);
      }
      isMouseSelecting = false;
    }
    triggerSelection();
  });

  textContainer.addEventListener('keyup', function () {
    triggerSelection();
  });

  document.addEventListener('keyup', function (event) {
    if (event.key === 'Escape') {
      ignoreSuggestion();
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initTextSelectionDelegation);
} else {
  initTextSelectionDelegation();
}
