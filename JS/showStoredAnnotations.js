/**
 * Visualize whatever is stored in the database and present it to the user.
 * When overlapping annotations are detected, these are shown in a modal which 
 * allows the user to select the specific annotation they want to inspect.
 */

window.spanMap = null;

function getStoredAnnotations() {
  if (typeof window !== 'undefined' && window.storedAnnotations) {
    return window.storedAnnotations;
  }
  if (typeof storedAnnotations !== 'undefined' && storedAnnotations) {
    return storedAnnotations;
  }
  return null;
}

function getAutomaticAnnotations() {
  if (typeof window !== 'undefined' && Array.isArray(window.automatic_annotations)) {
    return window.automatic_annotations;
  }
  if (typeof automatic_annotations !== 'undefined' && Array.isArray(automatic_annotations)) {
    return automatic_annotations;
  }
  return [];
}

function initSpanMap(force = false) {
  if (!force && window.spanMap && window.spanMap.length > 0) {
    return window.spanMap;
  }
  const textContent = document.getElementById('textcontent');
  if (!textContent) {
    return [];
  }
  const letters = textContent.getElementsByClassName('ltr');
  window.spanMap = new Array(letters.length);
  for (let l = 0; l < letters.length; l++) {
    const span = letters[l];
    const attr = span.getAttribute('data-itercounter');
    const iter = (attr !== null && attr !== '') ? parseInt(attr, 10) : l;
    window.spanMap[iter] = span;
  }
  return window.spanMap;
}

function getSpanByIter(i) {
  if (!window.spanMap) {
    initSpanMap();
  }
  if (window.spanMap && window.spanMap[i]) {
    return window.spanMap[i];
  }
  return document.querySelector(`.ltr[data-itercounter="${i}"]`);
}

function unmark() {
  const marked = document.querySelectorAll('.markedAnnotation');
  marked.forEach(element => {
    element.classList.remove('markedAnnotation');
  });
}

function markBasedOnId(id) {
  unmark();
  initSpanMap();
  let highlightedText = '';
  const stored = getStoredAnnotations();
  const autoAnnos = getAutomaticAnnotations();

  // Direct fast path: lookup start and stop in stored annotations relations
  if (stored && stored.relations && stored.relations[id]) {
    const anno = stored.relations[id];
    const start = parseInt(anno.start, 10);
    const stop = parseInt(anno.stop, 10);
    for (let i = start; i <= stop; i++) {
      const span = getSpanByIter(i);
      if (span) {
        span.classList.add('markedAnnotation');
        highlightedText += span.textContent;
      }
    }
  } else if (autoAnnos && Array.isArray(autoAnnos)) {
    const autoAnno = autoAnnos.find(a => a.annotation === id);
    if (autoAnno) {
      const start = parseInt(autoAnno.start, 10);
      const stop = parseInt(autoAnno.stop, 10);
      for (let i = start; i <= stop; i++) {
        const span = getSpanByIter(i);
        if (span) {
          span.classList.add('markedAnnotation');
          highlightedText += span.textContent;
        }
      }
    }
  }

  // Fallback scan across indexed spanMap if not found in relation structures
  if (!highlightedText && window.spanMap) {
    for (let l = 0; l < window.spanMap.length; l++) {
      const span = window.spanMap[l];
      if (span && span.dataset.annotation && span.dataset.annotation.split(',').includes(id)) {
        span.classList.add('markedAnnotation');
        highlightedText += span.textContent;
      }
    }
  }

  globalSelectionText = highlightedText;
}

function textByUUID(uuid) {
  /**
   * Takes the UUID from an annotation and returns the text that is contained by it.
   */
  initSpanMap();
  const stored = getStoredAnnotations();
  const autoAnnos = getAutomaticAnnotations();

  if (stored && stored.relations && stored.relations[uuid]) {
    const anno = stored.relations[uuid];
    let text = '';
    const start = parseInt(anno.start, 10);
    const stop = parseInt(anno.stop, 10);
    for (let i = start; i <= stop; i++) {
      const span = getSpanByIter(i);
      if (span) {
        text += span.textContent;
      }
    }
    return text;
  }

  if (autoAnnos && Array.isArray(autoAnnos)) {
    const autoAnno = autoAnnos.find(a => a.annotation === uuid);
    if (autoAnno) {
      let text = '';
      const start = parseInt(autoAnno.start, 10);
      const stop = parseInt(autoAnno.stop, 10);
      for (let i = start; i <= stop; i++) {
        const span = getSpanByIter(i);
        if (span) {
          text += span.textContent;
        }
      }
      return text;
    }
  }

  // Fallback scan
  let text = '';
  if (window.spanMap) {
    for (let l = 0; l < window.spanMap.length; l++) {
      const span = window.spanMap[l];
      if (span && span.dataset.annotation && span.dataset.annotation.split(',').includes(uuid)) {
        text += span.textContent;
      } else if (text !== '') {
        return text;
      }
    }
  }
  return text;
}

function makeMultiBox(ids) {
  /**
   * When multiple entities have to be shown, creates a modal box
   * that will load the different UUIDs for the user to see and choose from.
   */
  var [div, mode, topDst, height, leftDst] = makeBoxTemplate(); 
  div.setAttribute('id', 'multibox'); 
  let multiElementDiv = document.createElement('div'); 
  const stored = getStoredAnnotations();

  ids.forEach(element => {
    let displayText = textByUUID(element); 
    let textElement = document.createElement('p');
    textElement.classList.add('textElementMulti');
    textElement.dataset.for_annotation = element; 
    textElement.textContent = displayText;

    if (stored && stored.relations && stored.relations[element]) {
      let elmClass = stored.relations[element]['type']; 
      textElement.classList.add(elmClass);
    }
    textElement.onclick = (e) => {
      let elm = e.target.closest('.textElementMulti') || e.target;
      let uuid = elm.dataset.for_annotation;
      loadAnnotationData(uuid);
    };
    multiElementDiv.appendChild(textElement); 
  });

  div.appendChild(multiElementDiv); 
  div.classList.add('suggestionBox', 'bg-white');
  div.style.position = 'absolute';
  div.style.top = (topDst + height + 6) + 'px';
  div.style.left = leftDst + 'px';
  div.style.minWidth = '260px';
  div.style.maxWidth = '320px';

  let closeButton = document.createElement('button'); 
  let closeButtonText = document.createTextNode('Close'); 
  closeButton.appendChild(closeButtonText);
  closeButton.className = 'w-full mt-2 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition text-center'; 
  closeButton.onclick = () => {
    ignoreSuggestion("multibox");
  };
  div.appendChild(closeButton);

  document.body.appendChild(div);
}

function initAnnotationClickDelegation() {
  const container = document.getElementById('textcontent');
  if (!container || container.dataset.annotationClickBound) {
    return;
  }
  container.dataset.annotationClickBound = 'true';

  container.addEventListener('click', function (e) {
    const ltr = e.target.closest('.ltr');
    if (!ltr || !ltr.dataset.annotation) {
      return;
    }
    const relatedAnnotationIDS = ltr.dataset.annotation;
    const relatedAnnotations = relatedAnnotationIDS.split(',');
    markBasedOnId(relatedAnnotations[0]);
    if (relatedAnnotations.length > 1) {
      makeMultiBox(relatedAnnotations); 
    } else if (relatedAnnotations.length === 1 && typeof loadAnnotationData === 'function') {
      loadAnnotationData(relatedAnnotations[0]);
    }
  });
}

function visualizeStoredAnnotations() {
  initSpanMap(true);
  var positions = {};
  const stored = getStoredAnnotations();
  const autoAnnos = getAutomaticAnnotations();

  if (stored && stored.relations) {
    for (const [key, properties] of Object.entries(stored.relations)) {
      if (!properties) continue;
      const starts = parseInt(properties['start'], 10);
      const stops = parseInt(properties['stop'], 10);
      const type = properties['type'];
      if (isNaN(starts) || isNaN(stops)) continue;
      for (let i = starts; i <= stops; i++) {
        const j = i.toString();
        if (!(j in positions)) {
          positions[j] = [[key], [type]];
        } else {
          positions[j][0].push(key);
          positions[j][1].push(type);
        }
      }
    }
  }

  if (autoAnnos && Array.isArray(autoAnnos)) {
    for (const auto_anno of autoAnnos) {
      if (!auto_anno) continue;
      const starts = parseInt(auto_anno['start'], 10);
      const stops = parseInt(auto_anno['stop'], 10);
      const key = auto_anno['annotation'];
      if (isNaN(starts) || isNaN(stops)) continue;
      for (let i = starts; i <= stops; i++) {
        const j = i.toString();
        if (!(j in positions)) {
          positions[j] = [[key], ['app_automatic']];
        } else {
          positions[j][0].push(key);
          positions[j][1].push('app_automatic');
        }
      }
    }
  }

  // Paint the annotations directly into the indexed spanMap in batch
  for (const [posStr, annoData] of Object.entries(positions)) {
    const iter = parseInt(posStr, 10);
    const ltr = getSpanByIter(iter);
    if (ltr) {
      ltr.classList.add('linked', 'underline');
      ltr.dataset.annotation = annoData[0].join(',');
      ltr.dataset.annotationCount = annoData[0].length;
      annoData[1].forEach(cls => {
        if (cls) {
          ltr.classList.add(cls);
        }
      });
    }
  }

  // Attach single delegated click listener to container
  initAnnotationClickDelegation();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    initAnnotationClickDelegation();
    const stored = getStoredAnnotations();
    const autoAnnos = getAutomaticAnnotations();
    if ((stored && stored.relations && Object.keys(stored.relations).length > 0) || (autoAnnos && autoAnnos.length > 0)) {
      visualizeStoredAnnotations();
    }
  });
}

