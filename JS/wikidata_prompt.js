function wdprompt(string, offset = 0) {
  /**
   *  uses the given search string to look up wd entities; offset is the xth page that'll be loaded 
   * by performing the request. 
   * 
   * This call should empty the div with ID WDResponseTarget otherwise a new search will retain old
   * properties in the DOM causing confusion. 
   * 
   */
  let wdboxToDrop = document.getElementById('WDResponseTarget');
  if (wdboxToDrop) { wdboxToDrop.remove(); }

  // Resolve lookup language using the active string matching language resolution.
  let language = '';
  if (typeof getActiveStringMatchingLanguage === 'function') {
    const langInfo = getActiveStringMatchingLanguage();
    if (langInfo && langInfo.iso) {
      language = langInfo.iso;
    }
  }
  if (!language) {
    const dd = document.getElementById('wdlookuplanguage');
    language = (dd && dd.value) ? dd.value.trim() : '';
  }
  if (!language && typeof languageOptions !== 'undefined' && languageOptions && languageOptions.ISO_code) {
    language = (typeof normalizeLanguageCode === 'function')
      ? normalizeLanguageCode(languageOptions.ISO_code)
      : String(languageOptions.ISO_code).trim().toLowerCase().split(/[-_]/)[0];
  }
  if (!language) {
    const domCodeElem = document.getElementById('detectedLanguageCode');
    const domCode = domCodeElem ? domCodeElem.textContent.trim().toLowerCase() : '';
    if (domCode && domCode !== 'n/a' && domCode !== 'none' && domCode !== 'unknown') {
      language = (typeof normalizeLanguageCode === 'function')
        ? normalizeLanguageCode(domCode)
        : domCode.split(/[-_]/)[0];
    }
  }
  if (!language) {
    const titlesDict = (typeof wdProperties !== 'undefined' && wdProperties && wdProperties.stringmatchWikipediaTitles)
      ? wdProperties.stringmatchWikipediaTitles
      : {};
    const fallbackKeys = Object.keys(titlesDict);
    if (fallbackKeys.length > 0) {
      language = fallbackKeys[0].replace(/wiki$/, '');
    }
  }
  if (!language) {
    language = 'en';
  }

  // Synchronize the lookup language selector in the sidebar if present.
  const lookupElem = document.getElementById('wdlookuplanguage');
  if (lookupElem && lookupElem.value !== language) {
    if (!lookupElem.querySelector(`option[value="${language}"]`)) {
      const opt = document.createElement('option');
      opt.value = language;
      opt.textContent = language;
      lookupElem.appendChild(opt);
    }
    lookupElem.value = language;
  }

  const strictElem = document.getElementById('strictLookup');
  let strictLookup = strictElem ? strictElem.checked : false;
  const fallbackElem = document.getElementById('returnSameAsLookup');
  let useFallback = fallbackElem ? fallbackElem.checked : false;

  var extra1 = strictLookup ? '&strictlanguage=true' : '&strictlanguage=false';
  var extra2 = useFallback ? '&uselang=' + encodeURIComponent(language) : '';

  let by = 10;
  console.warn('STRINGVAL WD', string);
  //make sure to use encodeURIComponent for strings that have special characters (e.g. P&G)
  let promptURL = "https://www.wikidata.org/w/api.php?action=wbsearchentities&search=" + encodeURIComponent(string) + "&origin=*&format=json&errorformat=plaintext&type=item&language=" + language + extra1 + "&limit=" + by + "&continue=" + offset + extra2;
  const target = document.getElementById('wdpromptBox');
  target.classList.remove('hidden');
  let table = document.createElement('table');
  table.className = 'w-full text-left text-xs divide-y divide-slate-200 border border-slate-200 rounded-xl overflow-hidden my-3 shadow-2xs';
  fetch(promptURL)
    .then((response) => response.json())
    .then((data) => {
      // Needs a title item for Wikidata:
      var searchResults = data['search'];
      for (var s = 0; s < searchResults.length; s++) {
        let qid, title, descr = '';
        try {
          qid = searchResults[s]['id'];
        } catch (e) {
          qid = false;
        }
        try {
          title = searchResults[s]['display']['label']['value'];
        } catch (e) {
          title = '';
        }
        try {
          descr = searchResults[s]['display']['description']['value'];
        } catch (e) {
          descr = '';
        }
        if (qid) {
          let row = table.insertRow();
          row.className = 'border-b border-slate-100 hover:bg-indigo-50/50 transition group';
          let pickThisCell = row.insertCell();
          pickThisCell.className = 'p-2.5 text-center align-top w-10';
          let pickButton = document.createElement('button');
          pickButton.type = 'button';
          pickButton.className = 'inline-flex items-center justify-center w-6 h-6 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition cursor-pointer';
          pickButton.title = 'Select this entity';
          pickButton.innerHTML = '<i class="fas fa-check"></i>';
          pickButton.addEventListener('click', function () { pickThisQID(qid); });
          pickThisCell.appendChild(pickButton);

          let qidcell = row.insertCell();
          qidcell.className = 'p-2.5 font-mono text-xs align-top whitespace-nowrap';
          let qidlink = "https://www.wikidata.org/wiki/" + qid;
          let qidAnchor = document.createElement('a');
          qidAnchor.setAttribute('href', qidlink);
          qidAnchor.setAttribute('target', '_blank');
          qidAnchor.className = 'text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1';
          qidAnchor.innerHTML = qid + ' <i class="fas fa-external-link-alt text-[9px] opacity-70"></i>';
          qidcell.appendChild(qidAnchor);

          let titlecell = row.insertCell();
          titlecell.className = 'p-2.5 font-semibold text-slate-800 align-top';
          titlecell.textContent = title;

          let descrcell = row.insertCell();
          descrcell.className = 'p-2.5 text-slate-500 align-top text-xs';
          descrcell.textContent = descr;
        }
      }
      // Navigate offsets: 
      var navigateReply = document.createElement('div');
      navigateReply.className = 'flex items-center justify-between py-2 text-xs text-slate-600';
      let nextOffset, prevOffset;

      prevOffset = offset - by;
      let prevPage = document.createElement('button');
      prevPage.type = 'button';
      prevPage.className = 'inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium cursor-pointer shadow-2xs transition';
      prevPage.innerHTML = '<i class="fas fa-chevron-left text-[11px]"></i> Previous';
      if (offset === 0) {
        prevPage.classList.add('invisible');
      }
      prevPage.addEventListener('click', function () { wdprompt(string, prevOffset); });
      navigateReply.appendChild(prevPage);

      nextOffset = data['search-continue'];
      let nextPage = document.createElement('button');
      nextPage.type = 'button';
      nextPage.className = 'inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium cursor-pointer shadow-2xs transition ml-auto';
      nextPage.innerHTML = 'Next <i class="fas fa-chevron-right text-[11px]"></i>';
      if (typeof nextOffset === "undefined") {
        nextPage.classList.add('invisible');
      }
      nextPage.addEventListener('click', function () { wdprompt(string, nextOffset); });
      navigateReply.appendChild(nextPage);

      target.innerHTML = '';
      target.appendChild(navigateReply);
      target.appendChild(table);

      if (searchResults.length == 0){
        let noResults = document.createElement('div');
        noResults.className = 'p-3 my-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2';
        noResults.innerHTML = '<i class="fas fa-info-circle text-amber-500 text-sm"></i> <span>Wikidata returned no results for <strong>' + encodeURIComponent(string) + '</strong>. Change the spelling to try again.</span>';
        target.appendChild(noResults); 
      }
    });
}

let chosenQID = null;

function syncChosenQIDToPropertyInputs(qid) {
  const value = (qid !== -1 && qid !== null && qid !== undefined) ? String(qid) : '';
  const wdInputs = document.querySelectorAll('#propertyBox .validateAs_wikidata, #propertyBox input[data-datatype="wikidata"], #propertyBox input[pattern*="^Q"]');
  wdInputs.forEach(inp => {
    inp.value = value;
    inp.dispatchEvent(new Event('input', { bubbles: true }));
    inp.dispatchEvent(new Event('change', { bubbles: true }));
  });
}

async function acceptQID(qid = -1) {
  /* Start lookup if an entity in this database has the assigned QID.
   * If a single existing entity matches this QID, resolve immediately to that entity
   * (adding the selected string as a spelling variant) without intermediate UI repaints.
   * Pass -1 to skip the check and go straight to the entity creation process.
   */
  let element = document.getElementById('wdsearchpromptbox');
  if (element !== null) {
    element.remove();
  }
  const confGroup = document.getElementById('embeddedWDConfirmationGroup');
  if (confGroup) {
    confGroup.remove();
  }
  const respTarget = document.getElementById('WDResponseTarget');
  if (respTarget) {
    respTarget.remove();
  }

  chosenQID = (qid !== -1 && qid !== null) ? qid : null;
  syncChosenQIDToPropertyInputs(chosenQID);

  if (qid !== -1 && qid !== null) {
    try {
      const res = await fetch('/AJAX/checkWDExists.php?qid=' + encodeURIComponent(qid));
      const data = await res.json();
      const hits = (data && Array.isArray(data.data)) ? data.data : [];

      if (hits.length === 1 && typeof window.quickLinkSpanToEntity === 'function' && typeof globalSelectionText === 'string' && globalSelectionText.trim() !== '') {
        await window.quickLinkSpanToEntity(hits[0], globalSelectionStart, globalSelectionEnd, globalSelectionText.trim(), false);
        return;
      }

      if (hits.length > 0) {
        // Multiple existing entities share this QID: render hit selector once.
        await checkIfConnectionExists(qid);
        return;
      }
    } catch (err) {
      console.warn('Could not check existing Wikidata entity for ' + qid, err);
    }
  }

  // No existing entity found (or skipped with -1): reveal entity creation form once.
  let baseElem = document.getElementById('embeddedET') || document.getElementById('lattePromotionContainer');
  if (baseElem === null) {
    if (typeof buildAnnotationCreationBox === 'function') {
      buildAnnotationCreationBox();
    }
    const etMainElem = document.getElementById('etmain');
    if (etMainElem) {
      etMainElem.innerHTML = '';
    }
    baseElem = document.getElementById('embeddedET') || document.getElementById('lattePromotionContainer');
  }

  if (baseElem) {
    baseElem.classList.remove('hidden');
  }
  let creationElement = document.getElementById('etcreate') || document.getElementById('lattePromotionContainer');
  if (creationElement) {
    creationElement.classList.add('getAttention');
  }
}
function pickThisQID(qid) {
  /*
    Will load the WIKIDATA properties of a given QID when a user clicks
    the green tick mark in the table of the WDpromptbox.

    Data is only stored persistent after accepting!! There is a chance that
    the user does not want to commit this QID. 
  */
  chosenQID = qid;
  // Populate any visible wikidata property input immediately so promotion saves the picked QID.
  syncChosenQIDToPropertyInputs(chosenQID);
  //console.log(qid); 
  //clear the promptbox:
  document.getElementById('wdpromptBox').classList.add('hidden');
  //load the wikidata.js class and set qidmode on qid!
  var wd = new wikibaseEntry(qid, wdProperties, 'slideover', 'qid');
  wd.getWikidata()
    .then(function () { wd.renderEntities(qid) });
  //if the user is unsure, allow them to go back to the selector layout: 
  //if the user is SURE ==> provide a save button which sends the request to the server! 
  // if there's no logged in user: disable set of buttons!
  //    check if the server holds a userid for the given session! ==> serverside check!
  //    if yes, enable the buttons,
  let rejectButton = document.createElement('button');
  let acceptButton = document.createElement('button');
  rejectButton.type = 'button';
  acceptButton.type = 'button';
  rejectButton.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs shadow-2xs transition cursor-pointer';
  acceptButton.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-2xs transition cursor-pointer';
  rejectButton.innerHTML = '<i class="fas fa-times"></i> Reject';
  acceptButton.innerHTML = '<i class="fas fa-check"></i> Accept';

  const element = document.getElementById('embeddedWDConfirmationGroup');
  // If the element exists, remove it from the DOM
  if (element) {
    element.remove();
  }
  rejectButton.addEventListener('click', function () {
    wd = null; // Destroy wikidataObject
    chosenQID = null;
    syncChosenQIDToPropertyInputs(null);
    let inputPrompt = document.getElementById('wikidataInputPrompter'); 
    if(inputPrompt!== null){
      wdprompt(inputPrompt.value, 0);
    }
    deleteIfExistsById('embeddedWDConfirmationGroup'); 
    deleteIfExistsById('WDResponseTarget');
  });
  acceptButton.addEventListener('click', function () {
    // When normally annotating an entity then selecting and accepting a Q-id: 
    console.log('Calling acceptQID with: ', qid); 
    acceptQID(qid);
  });

  const displayWDtarget = document.getElementById('WDResponseTarget');
  let confirmationDiv = document.createElement('div');
  confirmationDiv.setAttribute('id', 'embeddedWDConfirmationGroup');
  confirmationDiv.className = 'flex items-center justify-end gap-2 p-3 my-3 bg-slate-50 border border-slate-200/90 rounded-xl shadow-2xs';
  confirmationDiv.appendChild(rejectButton);
  confirmationDiv.appendChild(acceptButton);
  // Call to /user/AJAX/profilestate.php => logincheck
  fetch("/user/AJAX/profilestate.php")
    .then(response => response.json())
    .then(result => {
      if (result['valid']){
        // There's a logged in user: show buttons
        document.getElementById('slideoverDynamicContent').insertBefore(confirmationDiv, displayWDtarget);
      }else{
        let loginwarningDiv = document.createElement('div');
        loginwarningDiv.className = 'p-3 my-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2';
        loginwarningDiv.innerHTML = '<i class="fas fa-info-circle text-amber-500"></i> <span>Further interactions with the database are limited for non-logged in users.</span>';
        document.getElementById('slideoverDynamicContent').insertBefore(loginwarningDiv, displayWDtarget); 
      }
    })
    .catch(error => {
      console.error('Error checking profile state:', error);
    });
}

function showHit(id) {
  /**
   * When a wikidata ID is shared among multiple entities. This 
   * function will display each given entity in a DIV until the 
   * user assigns the selected string to a given entity. 
   * 
  */
  //delete the call tot updateState: it get overridden in showET anyway!
  //updateState('State', 'There is one or more entity in the database with the same wikidata Q-identifier. You can connect it to one of these, or create a new entity.'); 
  var xhr = new XMLHttpRequest();
  xhr.open('GET', '/AJAX/getETById.php?id=' + id + '&extended=1', true);
  // Set up a callback function, make it pass the responsedata to showET!
  xhr.onload = function() {
    if (xhr.status >= 200 && xhr.status < 300) {
      // Request was successful
      var jsonResponse = JSON.parse(xhr.responseText);
      const etid = id; 
      const label = jsonResponse['extra']['label']; 
      const properties = jsonResponse['props']; 
      const qid = chosenQID;
      let variant_data = jsonResponse['variantsReformat']; 
      showET([etid, label, properties, qid], false, false, variant_data);
    } else {
      // Request failed
      console.error('Request failed with status: ' + xhr.status);
    }
  };
  // Send the request
  xhr.send();
}

let checkIfConnectionExists = async (qid) => {
  /**
   *  checks if the request QID (wikidata identifier) is already used by any
   *  entity in the backend. If so it will return a JSON object with a list of
   *  nodes that have this QID as wd identifier and a counter (int): hits for quick
   *  assessment of connectectd entities. You need to keep in mind that a single
   *  wikidata entity can be spread over multiple entities in the given project!
   *  Though we don't want this to be done through the interface, situations like
   *  this are not the norm!. 
   */
  var existingConnection = new Promise((resolve, reject) => {
    //make a .fetch call in javascript
    fetch("/AJAX/checkWDExists.php?qid=" + qid)
      .then((response) => response.json())
      .then((data) => {
        let hits = data['data'];

        let j = 0;
        if (data['hits'] != 0) {
          //load the first hit anyway: 
          let maintarget = document.getElementById('embeddedET');
          if (maintarget) {
            maintarget.innerHTML = '';
            maintarget.classList.remove('hidden');
            let contentNav = document.createElement('div');
            contentNav.setAttribute('id', 'navigateETs');
            let hitNav = document.createElement('div');
            hitNav.setAttribute('id', 'displayHitEt');
            maintarget.appendChild(contentNav);   //navigates through the hits.
            maintarget.appendChild(hitNav);       //prepares a box to display single hit info. 
            showHit(hits[j]);
          }
          //add navigationmenu if there's more than one option: 
          if (hits.length > 1) {
            function paginationIndicator(p) {
              if (p === 0) {
                navigateBack.classList.add('invisible');
              } else {
                navigateBack.classList.remove('invisible');
              }
              if (p + 1 === hits.length) {
                navigateNext.classList.add('invisible');
              } else {
                navigateNext.classList.remove('invisible');
              }
            }
            let navigateHits = document.createElement('div');
            navigateHits.className = 'flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs my-2';
            navigateBack = document.createElement('button');
            navigateBack.type = 'button';
            navigateBack.innerHTML = '<i class="fas fa-chevron-left"></i>';
            navigateNext = document.createElement('button');
            navigateNext.type = 'button';
            navigateNext.innerHTML = '<i class="fas fa-chevron-right"></i>';
            navigateBack.className = 'inline-flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 shadow-2xs transition cursor-pointer'; 
            navigateNext.className = 'inline-flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 shadow-2xs transition cursor-pointer'; 
            navigateState = document.createElement('span');
            navigateState.setAttribute('id', 'optionsIndicatorWD');
            navigateState.className = 'font-semibold text-slate-700 px-1';
            navigateState.appendChild(document.createTextNode((j + 1) + ' of ' + hits.length));
            navigateNext.addEventListener('click', function () {
              if (j + 1 < hits.length) {
                j++;
                navigateState.innerHTML = '';
                navigateState.appendChild(document.createTextNode((j + 1) + ' of ' + hits.length));
                paginationIndicator(j);
                showHit(hits[j]);
              }
            }); 
            navigateBack.addEventListener('click', function () {
              if (j > 0) {
                j--;
                paginationIndicator(j);
                showHit(hits[j]);
                navigateState.innerHTML = '';
                navigateState.appendChild(document.createTextNode(j + 1 + ' of ' + hits.length));
              }
            });
            paginationIndicator(j);
            navigateHits.appendChild(navigateBack);
            navigateHits.appendChild(navigateState);
            navigateHits.appendChild(navigateNext);
            let connectButton = document.createElement('button'); 
            connectButton.type = 'button';
            connectButton.innerHTML = '<i class="fas fa-link mr-1"></i> Connect'; 
            connectButton.className = 'inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-2xs transition ml-auto cursor-pointer'; 
            connectButton.addEventListener('click', function(){
              let annotationProperties = document.getElementById('embeddedAnnotation').getElementsByClassName('property');
              let annotationCollectionBox = extractAnnotationPropertiesFromDOM(annotationProperties);
              // Fetch a fresh CSRF token
              fetch('/user/AJAX/profilestate.php?fastconnect=1')
                .then((response) => response.json())
                .then((token) => {
                  if(token.valid){
                    connectAnnoToEntity(hits[j], languageOptions.nodeid, globalSelectionStart, globalSelectionEnd, globalSelectionText, annotationCollectionBox, token.csrf);
                  }
                });
            });
            let navelem = document.getElementById('navigateETs');
            if (navelem) {
              navelem.appendChild(navigateHits);
              navelem.appendChild(connectButton);
            }
          }
          resolve(data['hits']);
        } else {
          //let the user fill out the entity type and go from there
          //create flash box to prompt attention: 
          //OK
          let creationElement = document.getElementById('etcreate') || document.getElementById('lattePromotionContainer');
          if (creationElement) {
            creationElement.classList.add('getAttention');
          }
          if (!document.getElementById('lattePromotionContainer')) {
            loadPropertiesOfSelectedType(false);
          }
          resolve(0);
        }
      })
  })
  //return Promise; 
  return existingConnection;
}