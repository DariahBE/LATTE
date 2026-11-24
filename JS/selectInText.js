let globalSelectionText = null;
let globalSelectionStart = null;
let globalSelectionEnd = null;
let targetOfInfo = null; 


function ignoreSuggestion(box = "suggestionOnSelect") {
  var isOpen = document.getElementById(box);
  if (isOpen) {
    isOpen.remove();
  }
  toggleSlide(0);
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

function toggleSlide(dir = 0) {
  chosenQID = null;
  // 0 closes the sidepanel; 1 opens it. Better than the original .toggle() functions
  if (dir === 0) {
    unmark(); //when closing the side panel, always remove the markup!
    if (window.LinkingController && typeof window.LinkingController.reset === 'function') {
      window.LinkingController.reset();
    }
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

function deleteIfExistsById(id){
  let elem = document.getElementById(id); 
  if (elem !== null){
    elem.remove(); 
  }
}

let wikidataID; 
function updateState(key, msg){
  /**
   * To show the user why the program goes into a specific mode; explain what's going on!
   */
  const keyEl = document.getElementById('usernoticekey');
  const valEl = document.getElementById('usernoticevalue');
  if (keyEl) keyEl.textContent = key + ': ';
  if (valEl) valEl.textContent = msg;
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
  datadiv.appendChild(dataOnSearch);
  const spinnerEl = document.getElementById('suggestionboxspinner');
  if (datamode === 'stored' || datamode === 'automated') {
    if (spinnerEl && spinnerEl.parentNode) {
      spinnerEl.parentNode.insertBefore(datadiv, spinnerEl);
      spinnerEl.remove();
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

    if (hasNonCore && !isAuto && typeof window.LinkingController !== 'undefined') {
      window.LinkingController.openForSelection(selectedText, selectedTextStart, selectedTextEnd);
      return;
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
