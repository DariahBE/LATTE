let datamode = null;
let globalLoginAvailable = false; 
let globalAnnoInteractId = false;

function checklogin() {
  return fetch("/user/AJAX/profilestate.php")
    .then(res => res.json())
    .then(result => result['valid'])
    .catch(() => false);
}
checklogin()
  .then(valid => {
    globalLoginAvailable = valid;
  })
  .catch(() => {
    globalLoginAvailable = false; 
  });

function getInfoFromBackend(url) {
  return fetch(url)
    .then(res => res.json());
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


function createStableLinkingBlock(nodeid, stableURI) {
  // Generates clean action badges to the graph explorer and stable record
  var linkToGraphExplorer = '/explore/' + nodeid;
  var linkElement = document.createElement('a');
  linkElement.setAttribute('href', linkToGraphExplorer);
  linkElement.setAttribute('target', '_blank');
  linkElement.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition shadow-2xs';
  linkElement.innerHTML = '<i class="fas fa-project-diagram text-[11px]"></i><span>Explore in graph</span>';

  var linkToStablePage = document.createElement('a');
  linkToStablePage.setAttribute('href', stableURI);
  linkToStablePage.setAttribute('target', '_blank');
  linkToStablePage.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition shadow-2xs';
  linkToStablePage.innerHTML = '<i class="fas fa-anchor text-[11px]"></i><span>Stable record</span>';

  subdivGateway = document.createElement('div');
  subdivGateway.className = 'flex flex-wrap items-center gap-2 my-3';
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
    
    annotationPart.className = 'w-full mb-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80';
    let annotationSectionHeader = document.createElement('h4'); 
    annotationSectionHeader.appendChild(document.createTextNode('Annotation actions:'));
    annotationSectionHeader.className = 'text-xs font-bold uppercase tracking-wider text-slate-500 mb-2';
    annotationPart.appendChild(annotationSectionHeader); 

    let annotationActions = document.createElement('div');
    annotationActions.className = 'flex items-center gap-2';

    let annotationDelete = document.createElement('button'); 
    annotationDelete.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition shadow-2xs';
    annotationDelete.appendChild(document.createTextNode('Delete annotation'));

    let annotationEdit = document.createElement('button'); 
    annotationEdit.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition shadow-2xs';
    annotationEdit.appendChild(document.createTextNode('Edit annotation'));

    annoeditElement.appendChild(annotationEdit); 
    annodeleteElement.appendChild(annotationDelete); 
    if (annoupdate) {
      annotationActions.appendChild(annoeditElement);
    }
    if (annodelete) {
      annotationActions.appendChild(annodeleteElement); 
    }
    annotationPart.appendChild(annotationActions);
  }

  let entityEditLink = '/crud/edit.php?id=' + etnodeid;
  let entityDeleteLink = '/crud/delete.php?id=' + etnodeid;
  let etEditElement = document.createElement('a'); 
  etEditElement.setAttribute('href', entityEditLink); 
  let etDeleteElement = document.createElement('a'); 
  etDeleteElement.setAttribute('href', entityDeleteLink); 
  let entityPart = document.createElement('div'); 
  entityPart.className = 'w-full mb-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80';

  let entitySectionHeader = document.createElement('h4'); 
  entitySectionHeader.appendChild(document.createTextNode('Entity actions:'));
  entitySectionHeader.className = 'text-xs font-bold uppercase tracking-wider text-slate-500 mb-2';
  entityPart.appendChild(entitySectionHeader);

  let entityActions = document.createElement('div');
  entityActions.className = 'flex items-center gap-2';

  let entityDelete = document.createElement('button');
  entityDelete.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition shadow-2xs';
  entityDelete.appendChild(document.createTextNode('Delete entity'));

  let entityEdit = document.createElement('button'); 
  entityEdit.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition shadow-2xs';
  entityEdit.appendChild(document.createTextNode('Edit entity'));

  etEditElement.appendChild(entityEdit);
  etDeleteElement.appendChild(entityDelete);
  if (etupdate) {
    entityActions.appendChild(etEditElement);
  }
  if (etdelete) {
    entityActions.appendChild(etDeleteElement);
  }
  entityPart.appendChild(entityActions);

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
    const rawUri = String(data || '').trim();
    if (rawUri !== '') {
      if (/^https?:\/\//i.test(rawUri)) {
        var fieldvalue = document.createElement('a');
        fieldvalue.classList.add('externalURILogo');
        fieldvalue.setAttribute('href', rawUri);
        fieldvalue.setAttribute('target', '_blank');
        fieldvalue.setAttribute('rel', 'noopener noreferrer');
        fieldvalue.appendChild(document.createTextNode(rawUri));
        field.appendChild(fieldvalue);
        fieldvalue.setAttribute('type', 'url');
      } else {
        var fieldvalue = document.createElement('span');
        fieldvalue.appendChild(document.createTextNode(rawUri));
        field.appendChild(fieldvalue);
      }
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

// Render promotion interface allowing an automated suggestion to be promoted to a custom entity type.
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
    const sugStartVal = parseInt(data.annotation.properties[startcode][1] ?? 0, 10);
    const sugStopVal = parseInt(data.annotation.properties[stopcode][1] ?? 0, 10);
    globalSelectionStart = sugStartVal;
    globalSelectionEnd = sugStopVal;
    auto_annotation_internal_id = data['neo_id_of_auto_anno'] || 0; 
    window.currentLatteSuggestionId = data['suggestion_id'] || null;
    window.currentTextNeoId = data['text_neo_id'] || (typeof languageOptions !== 'undefined' ? languageOptions.nodeid : null); 

    let snippet = '';
    if (typeof getSelectedTextSnippet === 'function') {
      snippet = getSelectedTextSnippet(sugStartVal, sugStopVal);
    }
    if (!snippet) {
      const spans = [];
      for (let i = sugStartVal; i <= sugStopVal; i++) {
        const s = (typeof getSpanByIter === 'function') ? getSpanByIter(i) : document.querySelector(`.ltr[data-itercounter="${i}"]`);
        if (s) spans.push(s.textContent);
      }
      snippet = spans.join('');
    }
    globalSelectionText = snippet;
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
    // console.log('annotation values: '); 
    // console.log(rowkey, rowdata, protected, annotationStructure); 
  
  });
  //for all annotationExtraFields create a new editable field:
  for (var i = 0; i < annotationExtraFields.length; i++) {
    annotationTarget.appendChild(writeField(annotationExtraFields[i], '', false, annotationStructure));
  }

  // If viewing an automated suggestion and user has edit rights, render action buttons.
  if (datamode === 'automated') {
    const sugId = data['suggestion_id'];
    const sugStart = parseInt(data.annotation?.properties?.[startcode]?.[1] ?? 0, 10);
    const sugStop = parseInt(data.annotation?.properties?.[stopcode]?.[1] ?? 0, 10);
    const detectedType = data.annotation?.properties?.['detected_type']?.[1] || 'Entity';
    const canEdit = data['can_remove'] || (typeof window.userHasEditRights !== 'undefined' && window.userHasEditRights);

    const hasNonCore = (typeof window.hasNonCoreEntities !== 'undefined')
      ? Boolean(window.hasNonCoreEntities)
      : (typeof coreNodes !== 'undefined' && coreNodes.length > 0);

    // If no custom entity types defined in schema, render schema notice directly in this single pass
    if (!hasNonCore) {
      const kbBox = document.getElementById('urlrelations');
      if (kbBox) {
        kbBox.classList.add('hidden');
        kbBox.style.setProperty('display', 'none', 'important');
      }

      const noticeCard = document.createElement('div');
      noticeCard.id = 'schemaNoticeCard';
      noticeCard.className = 'schema-notice mt-4 p-3.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs space-y-1.5';
      noticeCard.innerHTML = `
        <div class="flex items-center gap-2 font-semibold text-amber-800">
          <i class="fas fa-info-circle text-amber-600"></i>
          <span>Schema notice</span>
        </div>
        <p class="leading-relaxed text-amber-700">
          No entity types outside of the core components have been defined. You can inspect, re-trim, or delete this suggestion, but custom entity types must be configured in the schema before linking it as a stored entity.
        </p>
      `;
      annotationTarget.appendChild(noticeCard);
    } else if (canEdit) {
      if (typeof window.LinkingController !== 'undefined') {
        window.LinkingController.openForAutomaticSuggestion(data, sugStart, sugStop, globalSelectionText, sugId, detectedType);
        return;
      }
    }

    if (canEdit && sugId) {
      const actionsCard = document.createElement('div');
      actionsCard.className = 'mt-4 pt-3 border-t border-slate-200 flex items-center gap-2';
      actionsCard.innerHTML = `
        <button type="button" id="slideoverRetrimBtn" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition cursor-pointer shadow-2xs">
          <i class="fas fa-arrows-alt-h text-[11px]"></i>
          <span>Retrim bounds</span>
        </button>
        <button type="button" onclick="removeAutomaticSuggestion('${sugId}')" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition cursor-pointer shadow-2xs">
          <i class="fas fa-trash-alt text-[11px]"></i>
          <span>Delete suggestion</span>
        </button>
      `;
      annotationTarget.appendChild(actionsCard);

      setTimeout(() => {
        document.getElementById('slideoverRetrimBtn')?.addEventListener('click', function() {
          if (typeof toggleSlide === 'function') toggleSlide(0);
          if (typeof startRetrimming === 'function') {
            startRetrimming(sugId, parseInt(sugStart, 10), parseInt(sugStop, 10), detectedType);
          }
        });
      }, 0);
    }
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
    const entityProperties = (data['entity'] && data['entity'][0] && data['entity'][0].properties) ? data['entity'][0].properties : {};
    Object.keys(entityProperties).forEach(key => {
      var row = entityProperties[key];
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
    if (typeof window.userHasEditRights !== 'undefined' && window.userHasEditRights) {
      const annoStartVal = parseInt(data?.annotation?.properties?.[startcode]?.[1] ?? -1, 10);
      const annoStopVal = parseInt(data?.annotation?.properties?.[stopcode]?.[1] ?? -1, 10);
      let matchedTextSnippet = '';
      if (annoStartVal >= 0 && annoStopVal >= annoStartVal) {
        if (typeof getSelectedTextSnippet === 'function') {
          matchedTextSnippet = getSelectedTextSnippet(annoStartVal, annoStopVal);
        } else {
          const spans = [];
          for (let i = annoStartVal; i <= annoStopVal; i++) {
            const s = document.querySelector(`.ltr[data-itercounter="${i}"]`);
            if (s) spans.push(s.textContent);
          }
          matchedTextSnippet = spans.join('');
        }
      }
      matchedTextSnippet = (matchedTextSnippet || '').trim();
      if (matchedTextSnippet !== '') {
        gateWay.appendChild(createPropagateOccurrencesBlock(neoid, matchedTextSnippet));
      }
    }
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
    if (wdLabelAccordingToModel !==  false && entityProperties[wdLabelAccordingToModel] !== undefined ) {
      /* It is not always possible to assign Q-IDs to an entity, so it could be undefined. Only run this block if there is a valid Wikidata Q-ID. */
      let qid = entityProperties[wdLabelAccordingToModel][1]; 
      let entityType = (data['entity'] && data['entity'][0] && data['entity'][0]['type']) ? data['entity'][0]['type'] : null;
      if (qid && typeof qid === 'string' && /^Q[0-9]+$/.test(qid.trim())) {
        var wd = new wikibaseEntry(qid.trim(), wdProperties, 'slideover', 'qid', entityType);
        wd.getWikidata()
          .then(function () { wd.renderEntities(qid.trim()); })
          .catch(function (err) { console.warn('Could not load Wikidata entry for ' + qid, err); });
      }
    }
  }

  // Update spinner with smooth fade in of 0.25 seconds for the final output
  if (annotationTarget) {
    annotationTarget.classList.remove('slideover-fade-in');
    void annotationTarget.offsetWidth;
    annotationTarget.classList.add('slideover-fade-in');
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

let activeResolvingAnnotationId = null;
let lastAnnotationResolveTimestamp = 0;

function showSlideoverLoadingState() {
  if (typeof toggleSlide === 'function') {
    toggleSlide(1);
  }
  const titleEl = document.getElementById('slideover-title');
  if (titleEl) {
    titleEl.textContent = 'Entity & annotation inspector';
  }
  const content = document.getElementById('slideoverDynamicContent');
  if (!content) return;

  content.classList.remove('slideover-fade-in');
  content.innerHTML = `
    <div id="slideoverLoadingSpinner" class="w-full flex flex-col items-center justify-center py-24 px-4 text-center space-y-4">
      <div class="relative w-12 h-12 flex items-center justify-center">
        <div class="absolute inset-0 rounded-full border-2 border-indigo-100 animate-ping opacity-35"></div>
        <div class="w-10 h-10 rounded-full border-2 border-slate-200 border-t-indigo-600 animate-spin"></div>
        <i class="fas fa-fingerprint text-indigo-500 text-xs absolute"></i>
      </div>
      <div class="space-y-1">
        <p class="text-xs font-semibold text-slate-800">Resolving annotation</p>
        <p class="text-[11px] text-slate-400">Fetching graph properties and metadata...</p>
      </div>
      <div class="w-40 h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1">
        <div class="h-full bg-indigo-500/70 rounded-full animate-pulse w-3/4"></div>
      </div>
    </div>
  `;
}

function showSlideoverErrorState(message) {
  const content = document.getElementById('slideoverDynamicContent');
  if (!content) return;
  content.classList.remove('slideover-fade-in');
  void content.offsetWidth;
  const esc = (typeof window.escapeHtml === 'function') ? window.escapeHtml : (s => s);
  content.innerHTML = `
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5">
      <i class="fas fa-exclamation-circle text-rose-600 shrink-0 mt-0.5"></i>
      <div>
        <p class="font-semibold text-rose-900">Unable to load annotation</p>
        <p class="mt-1 text-rose-700 leading-relaxed">${esc(message)}</p>
      </div>
    </div>
  `;
  content.classList.add('slideover-fade-in');
}

function createPropagateOccurrencesBlock(entityNeoId, selectionText) {
  const container = document.createElement('div');
  container.className = 'mt-3 p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2';

  const title = document.createElement('div');
  title.className = 'text-xs font-semibold text-indigo-900 flex items-center gap-1.5';
  title.innerHTML = '<i class="fas fa-clone text-indigo-600"></i><span>Propagate entity in text</span>';

  const desc = document.createElement('p');
  desc.className = 'text-xs text-slate-600';
  desc.textContent = 'Link all unannotated occurrences of "' + selectionText + '" in this document to this entity.';

  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-2xs transition-colors cursor-pointer';
  btn.innerHTML = '<i class="fas fa-magic text-[11px]"></i><span>Apply to all occurrences</span>';

  const statusEl = document.createElement('div');
  statusEl.className = 'text-xs hidden';

  btn.addEventListener('click', async function () {
    if (typeof window.LinkingController !== 'undefined' && typeof window.LinkingController.openPropagationForConfirmed === 'function') {
      window.LinkingController.openPropagationForConfirmed(entityNeoId, 'this entity', selectionText);
      return;
    }
    const texNeoId = (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.nodeid)
      ? languageOptions.nodeid
      : (window.currentTextNeoId || 0);
    if (!texNeoId) return;

    btn.disabled = true;
    btn.classList.add('opacity-50', 'cursor-not-allowed');
    btn.textContent = 'Propagating...';
    statusEl.classList.add('hidden');

    try {
      const tokenRes = await fetch('/AJAX/getdisposabletoken.php?task=1');
      const freshToken = await tokenRes.json();
      const batchForm = new FormData();
      batchForm.append('sourceNeoID', entityNeoId);
      batchForm.append('texNeoid', texNeoId);
      batchForm.append('selection', selectionText);
      batchForm.append('csrf', freshToken);
      batchForm.append('matchCase', 'true');
      batchForm.append('wholeWord', 'true');

      const res = await fetch('/AJAX/crud/connect_all_occurrences.php', {
        method: 'POST',
        body: batchForm
      });
      const data = await res.json();
      statusEl.classList.remove('hidden');
      if (data.success && data.created_count > 0) {
        data.annotations.forEach(anno => {
          if (typeof displayUpdatedText === 'function') {
            displayUpdatedText(anno.type, anno.start, anno.stop, anno.uuid);
          }
          if (window.storedAnnotations && window.storedAnnotations.relations) {
            window.storedAnnotations.relations[anno.uuid] = {
              start: anno.start,
              stop: anno.stop,
              type: anno.type
            };
          }
        });
        statusEl.className = 'text-xs font-semibold text-emerald-700';
        statusEl.textContent = data.msg || ('Linked ' + data.created_count + ' additional occurrence(s).');
      } else if (data.success) {
        statusEl.className = 'text-xs text-slate-600';
        statusEl.textContent = data.msg || 'No additional unannotated occurrences found.';
      } else {
        statusEl.className = 'text-xs font-semibold text-rose-600';
        statusEl.textContent = data.error || 'Failed to propagate entity.';
      }
    } catch (err) {
      statusEl.classList.remove('hidden');
      statusEl.className = 'text-xs font-semibold text-rose-600';
      statusEl.textContent = 'Network error while propagating entity.';
    } finally {
      btn.disabled = false;
      btn.classList.remove('opacity-50', 'cursor-not-allowed');
      btn.innerHTML = '<i class="fas fa-magic text-[11px]"></i><span>Apply to all occurrences</span>';
    }
  });

  container.appendChild(title);
  container.appendChild(desc);
  container.appendChild(btn);
  container.appendChild(statusEl);
  return container;
}

function loadAnnotationData(annotationID = false) {
  var relatedAnnotations = []; 
  if (!(annotationID)){
    // Get annotationID in case of click event trigger: find source of event. 
    var eventsource = (typeof event !== 'undefined' && event) ? (event.source || event.target) : null;
    if (eventsource && eventsource.dataset && eventsource.dataset.annotation) {
      relatedAnnotations = eventsource.dataset.annotation.split(',');
      annotationID = relatedAnnotations[0];
    }
  }
  if (!annotationID && relatedAnnotations.length === 0) {
    return;
  }

  // Guard against duplicate concurrent calls for the same annotation
  const now = Date.now();
  if (annotationID && activeResolvingAnnotationId === annotationID && (now - lastAnnotationResolveTimestamp) < 400) {
    return;
  }
  activeResolvingAnnotationId = annotationID;
  lastAnnotationResolveTimestamp = now;

  // Single annotation resolution
  if(relatedAnnotations.length <= 1){
    // Immediately open slider drawer with subtle noticeable loading spinner
    showSlideoverLoadingState();

    const texNeoId = (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.nodeid)
      ? languageOptions.nodeid
      : (window.currentTextNeoId || '');
    const resolveUrl = "/AJAX/resolve_annotation.php?annotation=" + encodeURIComponent(annotationID)
      + (texNeoId ? "&texid=" + encodeURIComponent(texNeoId) : "");

    getInfoFromBackend(resolveUrl)
    .then((data) => {
      if (data['code']==-1){
        // Fallback for client-side or unpersisted LATTE suggestions (latte_sug_*).
        if (typeof annotationID === 'string' && annotationID.startsWith('latte_sug_') && typeof window.LinkingController !== 'undefined') {
          let sugStart = -1;
          let sugStop = -1;
          let detectedType = 'Entity';
          let snippet = '';

          if (Array.isArray(window.automatic_annotations)) {
            const matchSug = window.automatic_annotations.find(a => (a.annotation || a.id) === annotationID);
            if (matchSug) {
              sugStart = parseInt(matchSug.start !== undefined ? matchSug.start : matchSug.startPos, 10);
              sugStop = parseInt(matchSug.stop !== undefined ? matchSug.stop : matchSug.endPos, 10);
              detectedType = matchSug.label || matchSug.labelTex || 'Entity';
              snippet = matchSug.text || '';
            }
          }

          if (sugStart < 0 || sugStop < sugStart) {
            const matchingSpans = Array.from(document.querySelectorAll('#textcontent .ltr[data-annotation]'))
              .filter(sp => (sp.getAttribute('data-annotation') || '').split(',').includes(annotationID));
            if (matchingSpans.length > 0) {
              const iters = matchingSpans.map(sp => parseInt(sp.dataset.itercounter, 10)).filter(n => !isNaN(n));
              sugStart = Math.min(...iters);
              sugStop = Math.max(...iters);
              detectedType = matchingSpans[0].getAttribute('data-entitytype') || 'Entity';
              snippet = matchingSpans.map(sp => sp.textContent).join('');
            }
          }

          if (!snippet && sugStart >= 0 && sugStop >= sugStart) {
            if (typeof getSelectedTextSnippet === 'function') {
              snippet = getSelectedTextSnippet(sugStart, sugStop);
            } else {
              const parts = [];
              for (let idx = sugStart; idx <= sugStop; idx++) {
                const s = document.querySelector(`.ltr[data-itercounter="${idx}"]`);
                if (s) parts.push(s.textContent);
              }
              snippet = parts.join('');
            }
          }

          if (sugStart >= 0 && sugStop >= sugStart && snippet.trim() !== '') {
            window.LinkingController.openForAutomaticSuggestion({}, sugStart, sugStop, snippet, annotationID, detectedType);
            return;
          }
        }

        showSlideoverErrorState(data['error'] || 'Could not resolve annotation data.');
        handleError(''); 
      }else{
        globalAnnoInteractId = data['annotation']['neoid']; 
        showdata(data);
        if (!(window.LinkingController && window.LinkingController.isActive())) {
          updateState('State', 'An annotated entity was selected, you can now see the data held in the database.');
        }
      }
    })
    .catch(err => {
      showSlideoverErrorState('An error occurred while loading annotation data.');
      handleError(err);
    })
    .finally(() => {
      setTimeout(() => {
        if (activeResolvingAnnotationId === annotationID) {
          activeResolvingAnnotationId = null;
        }
      }, 200);
    });
  }
}

function addInteractionToEntities() {
  // Main text annotations are handled through delegation in initAnnotationClickDelegation.
  // This listener attaches only to linked elements outside #textcontent to prevent duplicate firing.
  var links = document.querySelectorAll('.linked:not(#textcontent .linked)');
  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function (e) {
      e.stopPropagation();
      loadAnnotationData();
    });
  }
}



function createDivider(string){
  /**
   * Used to structure the entity sidebar with extended info for
   * Wikidata, variants and entity.  
   */
  const divider = document.createElement('h3');
  const dividerstring = document.createTextNode(string);
  divider.appendChild(dividerstring);
  divider.className = 'text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 pb-1.5 mt-4 mb-3 flex items-center gap-1.5'; 
  return divider;
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', addInteractionToEntities);
} else {
  addInteractionToEntities();
}
