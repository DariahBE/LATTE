/*
  js file used to interact with the LATTE connector. 
  There are functions of other files referenced in here. 
*/

var foundEntities = false;
function entity_extraction_launcher(){
  //triggered by DOM element in text.php
  /*
    this is the main function for interacting with the LATTE connector. It will
    trigger all necessary AJAX-functions to get the backend to fetch the text,
    predict the language and extract all relevant Entities.
  */
  document.getElementById('connectorExpand').classList.remove('hidden'); 
  deleteIfExistsById('extractorTrigger'); 
  //determine the language of the text: 
  detectLanguage(languageOptions).then(function(result){
    displayLanguage(result);
    if(result && !result.error && languageOptions && languageOptions.ISO_code){
      // do not try to detect entities if there's no language detected by the initial function.
      getEntities(languageOptions);
    } else {
      deleteIfExistsById('extractorProgress');
      if (typeof showToast === 'function') {
        showToast('Could not detect text language. Check connector availability.', 'error');
      }
    }
  }).catch(function(err){
    console.warn('Language detection failed:', err);
    deleteIfExistsById('extractorProgress');
    if (typeof showToast === 'function') {
      showToast('Language detection failed. Check connector availability.', 'error');
    }
  });  
  document.getElementById('extractorProgress').classList.remove('hidden'); 
}

function getEntities(options){
  /*
    Part of the LATTE extraction pipeline: this function is triggered 
    after successful language detection. It will trigger the backend to
    extract entities from the text. Once extracted, entities will be displayed
    using the displayEntities function.
  */
  var language = options['ISO_code'];
  var nodeid = options['nodeid'];
  const params = new URLSearchParams({
    node: nodeid,
    lang: language
  });
  fetch("/AJAX/getEntities_async.php?" + params.toString())
    .then(response => response.json())
    .then(result => {
      foundEntities = result;
      displayEntities(foundEntities);
      deleteIfExistsById('extractorProgress'); 
      if (typeof showToast === 'function') {
        const cnt = (result && result.data) ? result.data.length : 0;
        showToast(`Named entity extraction completed. ${cnt} suggestions found.`, 'success');
      }
    })
    .catch(err => {
      console.error("Entity extraction failed:", err);
      deleteIfExistsById('extractorProgress'); 
      if (typeof showToast === 'function') {
        showToast('Entity extraction failed. Check connector availability.', 'error');
      }
    });
}

function findItercounterRange(segmentId) {
  /**
   * Function that looks for the start and stop boundary of a node by their
   * given segment_id.
   */
  let spans = document.querySelectorAll('span[data-segment_id="' + segmentId + '"]');
  if(spans.length < 1){
    return {
      lowest: -1,
      highest: -1
    }; 
  }
  let lowestValue = Number.MAX_SAFE_INTEGER;
  let highestValue = Number.MIN_SAFE_INTEGER;
  let selectedText = ''; 
  
  spans.forEach(element => {
    selectedText += element.textContent;
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
  let spans = document.querySelectorAll('span[data-segment_id="' + segment + '"]');
  spans.forEach(ltr => {
    deleteAttribute(ltr, 'data-entitytype');
    deleteAttribute(ltr, 'data-segment_id');
    ltr.removeEventListener('click', clickHandler);
    ltr.addEventListener('click', function(){loadAnnotationData();}); 
    ltr.classList.remove('automatic_unstored');
    ltr.classList.add('linked', 'underline', 'markedAnnotation');
    ltr.setAttribute('data-annotation', uuid);
  });
  loadAnnotationData(uuid);
}

function disableButtonByElemId(elemid){
  let button = document.getElementById(elemid);
  if (button) {
    button.disabled = true;
    button.classList.add('disabled');
  }
}

function deleteAttribute(fromElement, attributeName){
  fromElement.removeAttribute(attributeName); 
}

function persistSuggestionOfLatteConnector(segment){
  if(globalLoginAvailable){
    let bounds = findItercounterRange(segment); 
    let start = bounds['lowest'];
    let stop = bounds['highest'];
    globalSelectionText = bounds['text'];
    disableButtonByElemId('suggestionbox_saveButton'); 
    disableButtonByElemId('suggestionbox_dropButton');     
    if (start > -1 && stop >= start){
      fetch("/AJAX/getdisposabletoken.php?task=1")    
      .then(response => response.json())
      .then(data => {
        fetch("/AJAX/persist_auto_annotation.php?texid="+languageOptions.nodeid+"&starts="+start+"&stops="+stop+"&token="+data)
          .then(response => response.json())
          .then(data => {
            let uuid = data[0];
            updateSegmentedAnnotation(segment, uuid); 
          });
      })
      .catch(error => {
        console.error(error);
      });
    }
  }
}

function generateRandomIdAttribute(l = 12){
  const chars = '0123456789ABCDEF';
  let id = '';
  for (let i = 0; i < l; ++i) {
    id += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return id;
}

function displayEntities(entities){
  /**
   * Triggered after successful entity extraction of the text.
   * Visualizes entities in the text by adding persistent annotation classes and attributes.
   */
  function checkRange(obj, start, stop) {
    return Object.keys(obj).some(key => obj[key].start === start && obj[key].stop === stop);
  }
  
  const counterTarget = document.getElementById('amountOfEntities');
  const modelTarget = document.getElementById('usedEntityModel');
  if (counterTarget && entities && entities['meta']) {
    counterTarget.textContent = entities['meta']['found_entities_number'];
  }
  if (modelTarget && entities && entities['meta']) {
    modelTarget.textContent = entities['meta']['used_model'];
  }
  $foundEntities = entities['data'] || [];

  if (entities && Array.isArray(entities['persisted_suggestions'])) {
    window.automatic_annotations = entities['persisted_suggestions'];
    if (typeof automatic_annotations !== 'undefined') {
      automatic_annotations = window.automatic_annotations;
    }
  }

  const clearBtn = document.getElementById('clearAutoAnnotationsBtn');
  if (clearBtn && $foundEntities && $foundEntities.length > 0) {
    clearBtn.classList.remove('hidden');
  }
  const badge = document.getElementById('autoAnnoCountBadge');
  if (badge && $foundEntities) {
    badge.textContent = `${$foundEntities.length} suggestions`;
    badge.classList.remove('hidden');
  }

  let allAnnotations = {
    ...automatic_annotations,
    ...storedAnnotations.relations,
    ...(typeof rejected_annotations !== 'undefined' ? rejected_annotations : {})
  };

  for (var i = 0; i < $foundEntities.length; i++) {
    let et_start = $foundEntities[i]['startPos']; 
    let et_stop = $foundEntities[i]['endPos']; 
    let et_type = $foundEntities[i]['labelTex']; 
    if (checkRange(allAnnotations, et_start, et_stop)){
      continue; 
    }

    const persistedSug = (entities['persisted_suggestions'] && entities['persisted_suggestions'][i])
      ? entities['persisted_suggestions'][i]
      : null;
    let annoKey = persistedSug ? persistedSug['id'] : ('latte_sug_' + generateRandomIdAttribute());

    for (let itercounter = et_start; itercounter <= et_stop; itercounter++) {
      const element = (typeof getSpanByIter === 'function')
        ? getSpanByIter(itercounter)
        : document.querySelector(`.ltr[data-itercounter="${itercounter}"]`);
      if (element) {
        element.classList.add('app_automatic', 'linked', 'underline');
        element.setAttribute('data-annotation', annoKey);
        element.setAttribute('data-entitytype', et_type);
      }
    }
  }

  if (typeof initAnnotationClickDelegation === 'function') {
    initAnnotationClickDelegation();
  }
}

const clickHandler = () => {
  makeSuggestionBox();
};
