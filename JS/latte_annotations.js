/**
 * Client-side handler for persistent Latte connector suggestions.
 * Provides functions for clearing all automatic annotations, removing individual suggestions,
 * and synchronizing UI state with Neo4j text node properties.
 */

function clearAutomaticAnnotations() {
  if (!confirm('Are you sure you want to clear all automatic annotations from this text? This action cannot be undone.')) {
    return;
  }

  const textId = (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.nodeid)
    ? languageOptions.nodeid
    : (window.currentTextNeoId || null);

  if (!textId) {
    if (typeof showToast === 'function') {
      showToast('Missing text ID for clearing annotations.', 'error');
    } else {
      alert('Missing text ID for clearing annotations.');
    }
    return;
  }

  const btn = document.getElementById('clearAutoAnnotationsBtn');
  if (btn) {
    btn.disabled = true;
    btn.classList.add('opacity-50', 'cursor-not-allowed');
  }

  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

  fetch('/AJAX/clear_auto_annotations.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({ text_id: textId, csrf_token: csrfToken })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Remove automatic highlight classes and data attributes from all spans.
      const autoElements = document.querySelectorAll('.app_automatic, .automatic_unstored');
      autoElements.forEach(el => {
        el.classList.remove('app_automatic', 'automatic_unstored', 'markedAnnotation');
        // If span had only automatic annotation, remove linked and underline as well.
        const remainingAnno = el.getAttribute('data-annotation');
        if (!remainingAnno || remainingAnno.startsWith('latte_sug_')) {
          el.classList.remove('linked', 'underline');
          el.removeAttribute('data-annotation');
          el.removeAttribute('data-annotation-count');
        }
        el.removeAttribute('data-entitytype');
        el.removeAttribute('data-segment_id');
      });

      // Clear memory references.
      window.automatic_annotations = [];
      if (typeof automatic_annotations !== 'undefined') {
        automatic_annotations = [];
      }

      // Hide badge and clear button.
      if (btn) {
        btn.classList.add('hidden');
      }
      const badge = document.getElementById('autoAnnoCountBadge');
      if (badge) {
        badge.classList.add('hidden');
      }
      const headerCandidate = document.getElementById('headerAutomatedCandidates');
      if (headerCandidate) {
        headerCandidate.classList.add('hidden');
      }

      // Close slideover if currently inspecting an automated suggestion.
      if (typeof toggleSlide === 'function' && typeof datamode !== 'undefined' && datamode === 'automated') {
        toggleSlide(0);
        if (typeof ignoreSuggestion === 'function') {
          ignoreSuggestion();
        }
      }

      if (typeof showToast === 'function') {
        showToast('Automatic annotations cleared successfully.', 'success');
      }
    } else {
      if (typeof showToast === 'function') {
        showToast(data.error || 'Failed to clear automatic annotations.', 'error');
      } else {
        alert(data.error || 'Failed to clear automatic annotations.');
      }
      if (btn) {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
      }
    }
  })
  .catch(err => {
    console.error('Failed to clear automatic annotations:', err);
    if (typeof showToast === 'function') {
      showToast('An error occurred while clearing annotations.', 'error');
    } else {
      alert('An error occurred while clearing annotations.');
    }
    if (btn) {
      btn.disabled = false;
      btn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
  });
}

// Extract text content across span indices.
function getSelectedTextSnippet(start, stop) {
  let snippet = '';
  for (let i = start; i <= stop; i++) {
    const s = (typeof getSpanByIter === 'function')
      ? getSpanByIter(i)
      : document.querySelector(`.ltr[data-itercounter="${i}"]`);
    if (s) snippet += s.textContent;
  }
  return snippet;
}

// Check if a character offset is covered by any other automatic annotation.
function isIndexCoveredByOtherAutoSuggestion(iter, currentSuggestionId) {
  if (!Array.isArray(window.automatic_annotations)) return false;
  return window.automatic_annotations.some(a => {
    const id = a.annotation || a.id;
    if (id === currentSuggestionId) return false;
    const s = parseInt(a.start !== undefined ? a.start : a.startPos, 10);
    const e = parseInt(a.stop !== undefined ? a.stop : a.endPos, 10);
    return !isNaN(s) && !isNaN(e) && iter >= s && iter <= e;
  });
}

// Remove visual highlight classes from spans for given suggestion ids.
function unhighlightAutomaticSuggestions(suggestionIds) {
  if (!Array.isArray(suggestionIds)) suggestionIds = [suggestionIds];

  suggestionIds.forEach(id => {
    const targetSpans = document.querySelectorAll(`[data-annotation*="${id}"]`);
    targetSpans.forEach(span => {
      const raw = span.getAttribute('data-annotation') || '';
      const list = raw.split(',').filter(item => item && item !== id);
      if (list.length === 0) {
        span.classList.remove('app_automatic', 'automatic_unstored', 'markedAnnotation', 'linked', 'underline');
        span.removeAttribute('data-annotation');
        span.removeAttribute('data-annotation-count');
      } else {
        span.setAttribute('data-annotation', list.join(','));
        span.setAttribute('data-annotation-count', list.length);
        const hasAuto = list.some(item =>
          item.startsWith('latte_sug_') ||
          (Array.isArray(window.automatic_annotations) && window.automatic_annotations.some(a => (a.annotation === item || a.id === item)))
        );
        if (!hasAuto) {
          span.classList.remove('app_automatic', 'automatic_unstored');
        }
      }
    });
  });

  // Remove from window.automatic_annotations.
  if (Array.isArray(window.automatic_annotations)) {
    window.automatic_annotations = window.automatic_annotations.filter(
      a => !suggestionIds.includes(a.annotation || a.id)
    );
    if (typeof automatic_annotations !== 'undefined') {
      automatic_annotations = window.automatic_annotations;
    }
  }

  // Update header candidate badge count.
  const remainingCount = Array.isArray(window.automatic_annotations) ? window.automatic_annotations.length : 0;
  const badge = document.getElementById('autoAnnoCountBadge');
  if (badge) {
    if (remainingCount > 0) {
      badge.textContent = remainingCount;
    } else {
      badge.classList.add('hidden');
    }
  }
  const headerCandidate = document.getElementById('headerAutomatedCandidates');
  if (headerCandidate && remainingCount === 0) {
    headerCandidate.classList.add('hidden');
  }
  const clearBtn = document.getElementById('clearAutoAnnotationsBtn');
  if (clearBtn && remainingCount === 0) {
    clearBtn.classList.add('hidden');
  }
}

// Show modal dialog for single or multiple automatic annotation deletions.
function showDeleteAutoSuggestionModal(suggestionId, targetText, matchingOthers) {
  const existing = document.getElementById('latteDeleteAutoModal');
  if (existing) existing.remove();

  const count = matchingOthers ? matchingOthers.length : 0;
  const escHtml = (typeof window.escapeHtml === 'function')
    ? window.escapeHtml
    : (str => (str || '').replace(/[&<>'"]/g, tag => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
      }[tag] || tag)));

  const safeSnippet = escHtml(targetText);

  let messageHtml = '';
  let buttonsHtml = '';

  if (count === 0) {
    messageHtml = safeSnippet
      ? `Are you sure you want to delete the automatic annotation for <strong class="text-indigo-600 font-mono">"${safeSnippet}"</strong> in this text?`
      : 'Are you sure you want to delete this automatic annotation?';

    buttonsHtml = `
      <button type="button" id="btnCancelDeleteAuto" class="w-full sm:w-auto px-3.5 py-2 rounded-xl text-slate-500 hover:text-slate-800 font-semibold text-xs transition cursor-pointer">
        Cancel
      </button>
      <button type="button" id="btnConfirmDeleteSingleAuto" class="w-full sm:w-auto px-4 py-2 rounded-xl font-semibold text-xs transition cursor-pointer shadow-xs flex items-center justify-center gap-1.5" style="background-color: #e11d48 !important; color: #ffffff !important; border: 1px solid #be123c !important;">
        <i class="fas fa-trash-alt text-xs" style="color: #ffffff !important;"></i>
        <span style="color: #ffffff !important;">Delete</span>
      </button>
    `;
  } else {
    messageHtml = `Found <strong class="text-slate-900 font-semibold">${count} other automatic annotation${count > 1 ? 's' : ''}</strong> with the exact text match <strong class="text-indigo-600 font-mono">"${safeSnippet}"</strong> in this text.`;

    buttonsHtml = `
      <button type="button" id="btnCancelDeleteAuto" class="w-full sm:w-auto px-3.5 py-2 rounded-xl text-slate-500 hover:text-slate-800 font-semibold text-xs transition cursor-pointer">
        Cancel
      </button>
      <button type="button" id="btnDeleteSingleAuto" class="w-full sm:w-auto px-4 py-2 rounded-xl font-semibold text-xs transition cursor-pointer shadow-xs flex items-center justify-center gap-1.5" style="background-color: #e11d48 !important; color: #ffffff !important; border: 1px solid #be123c !important;">
        <i class="fas fa-trash-alt text-xs" style="color: #ffffff !important;"></i>
        <span style="color: #ffffff !important;">Delete only this one</span>
      </button>
      <button type="button" id="btnDeleteAllAuto" class="w-full sm:w-auto px-4 py-2 rounded-xl font-semibold text-xs transition cursor-pointer shadow-xs flex items-center justify-center gap-1.5" style="background-color: #e11d48 !important; color: #ffffff !important; border: 1px solid #be123c !important;">
        <i class="fas fa-trash-alt text-xs" style="color: #ffffff !important;"></i>
        <span style="color: #ffffff !important;">Delete all (${count + 1})</span>
      </button>
    `;
  }

  const modal = document.createElement('div');
  modal.id = 'latteDeleteAutoModal';
  modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-md w-full p-6 space-y-4 animate-in fade-in zoom-in-95 duration-150">
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
          <i class="fas fa-trash-alt text-base"></i>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="text-base font-bold text-slate-900">Delete automatic annotation</h3>
          <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
            ${messageHtml}
          </p>
        </div>
      </div>

      <div class="bg-amber-50 rounded-xl p-3 border border-amber-200/80 text-[11px] text-amber-900 flex items-center gap-2">
        <i class="fas fa-shield-alt text-amber-600 shrink-0"></i>
        <span>This applies exclusively to automatic annotations. Stored manual annotations will not be affected.</span>
      </div>

      <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2 pt-2 border-t border-slate-100">
        ${buttonsHtml}
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  const cleanup = () => {
    modal.remove();
    document.removeEventListener('keydown', handleKey);
  };

  const handleKey = (e) => {
    if (e.key === 'Escape') cleanup();
  };
  document.addEventListener('keydown', handleKey);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) cleanup();
  });

  document.getElementById('btnCancelDeleteAuto')?.addEventListener('click', cleanup);

  document.getElementById('btnConfirmDeleteSingleAuto')?.addEventListener('click', () => {
    cleanup();
    executeRemoveAutoSuggestions([suggestionId]);
  });

  document.getElementById('btnDeleteSingleAuto')?.addEventListener('click', () => {
    cleanup();
    executeRemoveAutoSuggestions([suggestionId]);
  });

  document.getElementById('btnDeleteAllAuto')?.addEventListener('click', () => {
    cleanup();
    const allIds = [suggestionId, ...matchingOthers.map(a => (a.annotation || a.id))];
    executeRemoveAutoSuggestions(allIds);
  });
}

// Send delete request for selected automatic suggestion ids.
function executeRemoveAutoSuggestions(suggestionIds) {
  if (!Array.isArray(suggestionIds) || suggestionIds.length === 0) return;

  const textId = (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.nodeid)
    ? languageOptions.nodeid
    : (window.currentTextNeoId || null);

  if (!textId) {
    if (typeof showToast === 'function') {
      showToast('Missing text ID for removing suggestions.', 'error');
    }
    return;
  }

  fetch('/AJAX/remove_auto_suggestion.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ text_id: textId, suggestion_ids: suggestionIds })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      unhighlightAutomaticSuggestions(suggestionIds);

      // Close slideover inspector and inline suggestion popup.
      if (typeof toggleSlide === 'function') {
        toggleSlide(0);
      }
      if (typeof ignoreSuggestion === 'function') {
        ignoreSuggestion();
      }

      const count = suggestionIds.length;
      if (typeof showToast === 'function') {
        showToast(`${count} automatic annotation${count > 1 ? 's' : ''} removed.`, 'success');
      }
    } else {
      if (typeof showToast === 'function') {
        showToast(data.error || 'Failed to remove automatic suggestions.', 'error');
      } else {
        alert(data.error || 'Failed to remove automatic suggestions.');
      }
    }
  })
  .catch(err => {
    console.error('Failed to remove suggestions:', err);
    if (typeof showToast === 'function') {
      showToast('An error occurred while removing suggestions.', 'error');
    }
  });
}

// Delete automatic suggestion with duplicate matching detection.
function removeAutomaticSuggestion(suggestionId) {
  if (!suggestionId) return;

  const currentSug = (Array.isArray(window.automatic_annotations))
    ? window.automatic_annotations.find(a => (a.annotation === suggestionId || a.id === suggestionId))
    : null;

  let targetText = currentSug ? currentSug.text : null;
  if (!targetText && currentSug && currentSug.start !== undefined && currentSug.stop !== undefined) {
    targetText = getSelectedTextSnippet(currentSug.start, currentSug.stop);
  }
  if (!targetText) {
    const spans = document.querySelectorAll(`[data-annotation*="${suggestionId}"]`);
    if (spans.length > 0) {
      targetText = Array.from(spans).map(s => s.textContent).join('');
    }
  }

  // Look for exact string matches among automatic annotations only.
  const otherMatchingSugs = (Array.isArray(window.automatic_annotations) && targetText)
    ? window.automatic_annotations.filter(a => {
        const id = a.annotation || a.id;
        if (id === suggestionId) return false;
        const text = a.text || getSelectedTextSnippet(a.start, a.stop);
        return text === targetText;
      })
    : [];

  // Always use the styled confirmation modal instead of native browser alert.
  showDeleteAutoSuggestionModal(suggestionId, targetText, otherMatchingSugs);
}

window.clearAutomaticAnnotations = clearAutomaticAnnotations;
window.removeAutomaticSuggestion = removeAutomaticSuggestion;

/* =========================================================================
   Interactive re-trimming system for LATTE suggestions with drag handles
   ========================================================================= */

window.isRetrimmingActive = false;
window.activeRetrimData = null;

let isDraggingRetrimHandle = false;
let currentDragHandleType = null; // 'start' or 'end'

/**
 * Start interactive retrimming for a given automatic suggestion.
 */
function startRetrimming(suggestionId, start, stop, label) {
  if (!suggestionId) return;

  // Dismiss any existing context menu / suggestion box.
  if (typeof ignoreSuggestion === 'function') {
    ignoreSuggestion();
  }

  // Close inspector slideover if open.
  if (typeof toggleSlide === 'function') {
    toggleSlide(0);
  }

  const startVal = parseInt(start, 10);
  const stopVal = parseInt(stop, 10);

  window.isRetrimmingActive = true;
  window.activeRetrimData = {
    suggestionId: suggestionId,
    start: startVal,
    stop: stopVal,
    originalStart: startVal,
    originalStop: stopVal,
    trackedMin: startVal,
    trackedMax: stopVal,
    label: label || 'Entity'
  };

  renderRetrimVisuals();
  renderRetrimToolbar();

  if (typeof showToast === 'function') {
    showToast('Retrim mode active: drag handles on the ends or use the toolbar to adjust.', 'success', 3500);
  }
}

/**
 * Cancel retrimming and restore original boundaries.
 */
function cancelRetrimming() {
  if (!window.isRetrimmingActive || !window.activeRetrimData) return;

  const data = window.activeRetrimData;
  const originalStart = data.originalStart;
  const originalStop = data.originalStop;
  const sugId = data.suggestionId;
  const trackedMin = data.trackedMin;
  const trackedMax = data.trackedMax;

  cleanRetrimVisuals();

  // Clear any spans outside original bounds that were touched during retrimming.
  for (let i = trackedMin; i <= trackedMax; i++) {
    if (i < originalStart || i > originalStop) {
      const span = (typeof getSpanByIter === 'function')
        ? getSpanByIter(i)
        : document.querySelector(`.ltr[data-itercounter="${i}"]`);
      if (span && !isIndexCoveredByOtherAutoSuggestion(i, sugId)) {
        span.classList.remove('app_automatic', 'automatic_unstored', 'markedAnnotation', 'linked', 'underline');
      }
    }
  }

  // Restore original range highlight.
  applySuggestionHighlight(sugId, originalStart, originalStop, trackedMin, trackedMax);

  window.isRetrimmingActive = false;
  window.activeRetrimData = null;
  removeRetrimToolbar();

  if (typeof showToast === 'function') {
    showToast('Retrim cancelled.', 'info');
  }
}

/**
 * Save the retrimmed boundaries to the backend and update memory state.
 */
function saveRetrimming() {
  if (!window.isRetrimmingActive || !window.activeRetrimData) return;

  const data = window.activeRetrimData;
  const textId = (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.nodeid)
    ? languageOptions.nodeid
    : (window.currentTextNeoId || null);

  if (!textId) {
    if (typeof showToast === 'function') {
      showToast('Missing text ID for retrimming suggestion.', 'error');
    }
    return;
  }

  const saveBtn = document.getElementById('saveRetrimBtn');
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-[11px]"></i> <span>Saving...</span>';
  }

  fetch('/AJAX/retrim_auto_suggestion.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      text_id: textId,
      suggestion_id: data.suggestionId,
      new_start: data.start,
      new_stop: data.stop
    })
  })
  .then(res => res.json())
  .then(resData => {
    if (resData.success && resData.suggestion) {
      const updated = resData.suggestion;
      const newStart = parseInt(updated.start, 10);
      const newStop = parseInt(updated.stop, 10);

      // Update in window.automatic_annotations.
      if (Array.isArray(window.automatic_annotations)) {
        const item = window.automatic_annotations.find(
          a => (a.annotation === data.suggestionId || a.id === data.suggestionId)
        );
        if (item) {
          item.start = newStart;
          item.stop = newStop;
          item.startPos = newStart;
          item.endPos = newStop;
          item.text = updated.text || '';
        }
      }

      cleanRetrimVisuals();
      applySuggestionHighlight(data.suggestionId, newStart, newStop, data.trackedMin, data.trackedMax);

      window.isRetrimmingActive = false;
      window.activeRetrimData = null;
      removeRetrimToolbar();

      if (typeof showToast === 'function') {
        showToast('Annotation retrimmed successfully.', 'success');
      }
    } else {
      if (typeof showToast === 'function') {
        showToast(resData.error || 'Failed to retrim annotation.', 'error');
      }
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-check text-[11px]"></i> <span>Save trim</span>';
      }
    }
  })
  .catch(err => {
    console.error('Retrimming error:', err);
    if (typeof showToast === 'function') {
      showToast('An error occurred while saving retrimmed annotation.', 'error');
    }
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-check text-[11px]"></i> <span>Save trim</span>';
    }
  });
}

/**
 * Remove retrim handles and temporary retrim classes from DOM.
 */
function cleanRetrimVisuals() {
  document.querySelectorAll('.latte-retrim-handle').forEach(el => el.remove());
  document.querySelectorAll('.app_automatic_retrimming').forEach(el => {
    el.classList.remove('app_automatic_retrimming');
  });
}

/**
 * Re-render highlight spans and draggable handles at boundaries.
 * Contracted spans have their grey highlight removed immediately.
 */
function renderRetrimVisuals() {
  if (!window.activeRetrimData) return;
  const data = window.activeRetrimData;
  const start = data.start;
  const stop = data.stop;

  // Track the minimum and maximum boundaries touched in this session.
  data.trackedMin = Math.min(data.trackedMin, start, data.originalStart);
  data.trackedMax = Math.max(data.trackedMax, stop, data.originalStop);

  cleanRetrimVisuals();

  // Highlight all spans across tracked range: active range is yellow, contracted/deselected is plain.
  for (let i = data.trackedMin; i <= data.trackedMax; i++) {
    const span = (typeof getSpanByIter === 'function')
      ? getSpanByIter(i)
      : document.querySelector(`.ltr[data-itercounter="${i}"]`);
    if (!span) continue;

    if (i >= start && i <= stop) {
      span.classList.add('app_automatic_retrimming');
      span.classList.remove('app_automatic');
    } else {
      span.classList.remove('app_automatic_retrimming');
      // If contracted and not part of another automatic suggestion, remove the grey highlight.
      if (!isIndexCoveredByOtherAutoSuggestion(i, data.suggestionId)) {
        span.classList.remove('app_automatic', 'automatic_unstored', 'markedAnnotation', 'linked', 'underline');
      }
    }
  }

  // Insert start handle before startSpan.
  const startSpan = (typeof getSpanByIter === 'function')
    ? getSpanByIter(start)
    : document.querySelector(`.ltr[data-itercounter="${start}"]`);
  if (startSpan && startSpan.parentNode) {
    const startHandle = document.createElement('span');
    startHandle.className = 'latte-retrim-handle latte-handle-start';
    startHandle.setAttribute('data-retrim-handle', 'start');
    startHandle.setAttribute('title', 'Drag to adjust start boundary');
    startHandle.innerHTML = '<i class="fas fa-grip-lines-vertical"></i>';
    startHandle.addEventListener('mousedown', onStartHandleMouseDown);
    startSpan.parentNode.insertBefore(startHandle, startSpan);
  }

  // Insert end handle after endSpan.
  const endSpan = (typeof getSpanByIter === 'function')
    ? getSpanByIter(stop)
    : document.querySelector(`.ltr[data-itercounter="${stop}"]`);
  if (endSpan && endSpan.parentNode) {
    const endHandle = document.createElement('span');
    endHandle.className = 'latte-retrim-handle latte-handle-end';
    endHandle.setAttribute('data-retrim-handle', 'end');
    endHandle.setAttribute('title', 'Drag to adjust end boundary');
    endHandle.innerHTML = '<i class="fas fa-grip-lines-vertical"></i>';
    endHandle.addEventListener('mousedown', onEndHandleMouseDown);
    if (endSpan.nextSibling) {
      endSpan.parentNode.insertBefore(endHandle, endSpan.nextSibling);
    } else {
      endSpan.parentNode.appendChild(endHandle);
    }
  }

  updateRetrimToolbarPreview();
}

/**
 * Apply permanent .app_automatic highlight to the new range while deselecting contracted spans.
 */
function applySuggestionHighlight(suggestionId, start, stop, trackedMin, trackedMax) {
  const min = (trackedMin !== undefined) ? Math.min(trackedMin, start) : start;
  const max = (trackedMax !== undefined) ? Math.max(trackedMax, stop) : stop;

  // Clear spans across tracked range that fall outside [start, stop].
  for (let i = min; i <= max; i++) {
    const span = (typeof getSpanByIter === 'function')
      ? getSpanByIter(i)
      : document.querySelector(`.ltr[data-itercounter="${i}"]`);
    if (!span) continue;

    if (i < start || i > stop) {
      const raw = span.getAttribute('data-annotation') || '';
      const list = raw.split(',').filter(id => id && id !== suggestionId);
      if (list.length === 0) {
        span.classList.remove('app_automatic', 'automatic_unstored', 'markedAnnotation', 'linked', 'underline');
        span.removeAttribute('data-annotation');
        span.removeAttribute('data-annotation-count');
      } else {
        span.setAttribute('data-annotation', list.join(','));
        span.setAttribute('data-annotation-count', list.length);
        if (!isIndexCoveredByOtherAutoSuggestion(i, suggestionId)) {
          span.classList.remove('app_automatic', 'automatic_unstored');
        }
      }
    }
  }

  // Clean any remaining spans holding this annotation ID outside [start, stop].
  const allSpans = document.querySelectorAll(`[data-annotation*="${suggestionId}"]`);
  allSpans.forEach(span => {
    const iter = span.dataset.itercounter !== undefined ? parseInt(span.dataset.itercounter, 10) : NaN;
    if (!isNaN(iter) && (iter < start || iter > stop)) {
      const raw = span.getAttribute('data-annotation') || '';
      const list = raw.split(',').filter(id => id && id !== suggestionId);
      if (list.length === 0) {
        span.classList.remove('app_automatic', 'automatic_unstored', 'markedAnnotation', 'linked', 'underline');
        span.removeAttribute('data-annotation');
        span.removeAttribute('data-annotation-count');
      } else {
        span.setAttribute('data-annotation', list.join(','));
        span.setAttribute('data-annotation-count', list.length);
        if (!isIndexCoveredByOtherAutoSuggestion(iter, suggestionId)) {
          span.classList.remove('app_automatic', 'automatic_unstored');
        }
      }
    }
  });

  // Apply highlight to the new [start, stop] range.
  for (let i = start; i <= stop; i++) {
    const span = (typeof getSpanByIter === 'function')
      ? getSpanByIter(i)
      : document.querySelector(`.ltr[data-itercounter="${i}"]`);
    if (span) {
      span.classList.add('app_automatic', 'linked', 'underline');
      const existing = span.getAttribute('data-annotation') || '';
      const list = existing ? existing.split(',').filter(Boolean) : [];
      if (!list.includes(suggestionId)) {
        list.push(suggestionId);
      }
      span.setAttribute('data-annotation', list.join(','));
      span.setAttribute('data-annotation-count', list.length);
    }
  }
}

function onStartHandleMouseDown(e) {
  e.preventDefault();
  e.stopPropagation();
  isDraggingRetrimHandle = true;
  currentDragHandleType = 'start';
  document.querySelector('.latte-handle-start')?.classList.add('is-dragging');
  window.addEventListener('mousemove', onRetrimMouseMove);
  window.addEventListener('mouseup', onRetrimMouseUp);
}

function onEndHandleMouseDown(e) {
  e.preventDefault();
  e.stopPropagation();
  isDraggingRetrimHandle = true;
  currentDragHandleType = 'end';
  document.querySelector('.latte-handle-end')?.classList.add('is-dragging');
  window.addEventListener('mousemove', onRetrimMouseMove);
  window.addEventListener('mouseup', onRetrimMouseUp);
}

function onRetrimMouseMove(e) {
  if (!isDraggingRetrimHandle || !window.activeRetrimData) return;

  const target = document.elementFromPoint(e.clientX, e.clientY);
  const span = target ? target.closest('.ltr') : null;
  if (!span || span.dataset.itercounter === undefined) return;

  const iter = parseInt(span.dataset.itercounter, 10);
  if (isNaN(iter)) return;

  if (currentDragHandleType === 'start') {
    if (iter <= window.activeRetrimData.stop) {
      if (window.activeRetrimData.start !== iter) {
        window.activeRetrimData.start = iter;
        renderRetrimVisuals();
      }
    }
  } else if (currentDragHandleType === 'end') {
    if (iter >= window.activeRetrimData.start) {
      if (window.activeRetrimData.stop !== iter) {
        window.activeRetrimData.stop = iter;
        renderRetrimVisuals();
      }
    }
  }
}

function onRetrimMouseUp(e) {
  if (isDraggingRetrimHandle) {
    isDraggingRetrimHandle = false;
    currentDragHandleType = null;
    window.removeEventListener('mousemove', onRetrimMouseMove);
    window.removeEventListener('mouseup', onRetrimMouseUp);
    renderRetrimVisuals();
  }
}

/**
 * Render floating action bar with live offset display, nudge buttons, and Save/Cancel.
 */
function renderRetrimToolbar() {
  removeRetrimToolbar();
  if (!window.activeRetrimData) return;

  const data = window.activeRetrimData;
  const bar = document.createElement('div');
  bar.id = 'latteRetrimBar';
  const escHtml = (typeof window.escapeHtml === 'function') ? window.escapeHtml : (str => str);

  bar.innerHTML = `
    <div class="flex items-center gap-2 border-r border-slate-200 pr-3">
      <i class="fas fa-arrows-alt-h text-indigo-600"></i>
      <span class="text-xs font-bold text-slate-800">Retrimming</span>
      <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">${escHtml(data.label)}</span>
    </div>

    <!-- Start boundary control -->
    <div class="flex items-center gap-1.5">
      <span class="text-[11px] font-semibold text-slate-500">Start:</span>
      <button type="button" id="nudgeStartLeft" class="btn-nudge" title="Expand start left">◀</button>
      <span id="retrimStartDisplay" class="font-mono text-xs font-bold text-slate-800 px-1">${data.start}</span>
      <button type="button" id="nudgeStartRight" class="btn-nudge" title="Shrink start right">▶</button>
    </div>

    <!-- End boundary control -->
    <div class="flex items-center gap-1.5">
      <span class="text-[11px] font-semibold text-slate-500">End:</span>
      <button type="button" id="nudgeEndLeft" class="btn-nudge" title="Shrink end left">◀</button>
      <span id="retrimEndDisplay" class="font-mono text-xs font-bold text-slate-800 px-1">${data.stop}</span>
      <button type="button" id="nudgeEndRight" class="btn-nudge" title="Expand end right">▶</button>
    </div>

    <!-- Live snippet preview -->
    <div id="retrimSnippetPreview" class="px-2.5 py-1 bg-slate-100 rounded-lg text-xs font-mono text-slate-700 max-w-xs truncate border border-slate-200" title="Selected text preview">
      "${escHtml(getSelectedTextSnippet(data.start, data.stop))}"
    </div>

    <!-- Action buttons -->
    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
      <button type="button" id="saveRetrimBtn" onclick="saveRetrimming()" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs flex items-center gap-1.5 transition cursor-pointer">
        <i class="fas fa-check text-[11px]"></i>
        <span>Save trim</span>
      </button>
      <button type="button" id="cancelRetrimBtn" onclick="cancelRetrimming()" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center gap-1 transition cursor-pointer">
        <i class="fas fa-times text-[11px]"></i>
        <span>Cancel</span>
      </button>
    </div>
  `;

  document.body.appendChild(bar);

  document.getElementById('nudgeStartLeft')?.addEventListener('click', () => {
    if (window.activeRetrimData && window.activeRetrimData.start > 0) {
      window.activeRetrimData.start--;
      renderRetrimVisuals();
    }
  });
  document.getElementById('nudgeStartRight')?.addEventListener('click', () => {
    if (window.activeRetrimData && window.activeRetrimData.start < window.activeRetrimData.stop) {
      window.activeRetrimData.start++;
      renderRetrimVisuals();
    }
  });
  document.getElementById('nudgeEndLeft')?.addEventListener('click', () => {
    if (window.activeRetrimData && window.activeRetrimData.stop > window.activeRetrimData.start) {
      window.activeRetrimData.stop--;
      renderRetrimVisuals();
    }
  });
  document.getElementById('nudgeEndRight')?.addEventListener('click', () => {
    if (window.activeRetrimData) {
      window.activeRetrimData.stop++;
      renderRetrimVisuals();
    }
  });
}

function updateRetrimToolbarPreview() {
  if (!window.activeRetrimData) return;
  const startEl = document.getElementById('retrimStartDisplay');
  const endEl = document.getElementById('retrimEndDisplay');
  const prevEl = document.getElementById('retrimSnippetPreview');
  if (startEl) startEl.textContent = window.activeRetrimData.start;
  if (endEl) endEl.textContent = window.activeRetrimData.stop;
  if (prevEl) {
    const txt = getSelectedTextSnippet(window.activeRetrimData.start, window.activeRetrimData.stop);
    prevEl.textContent = `"${txt}"`;
    prevEl.title = txt;
  }
}

function removeRetrimToolbar() {
  const bar = document.getElementById('latteRetrimBar');
  if (bar) bar.remove();
}

// Global keyboard listeners for retrim mode
document.addEventListener('keydown', function(e) {
  if (!window.isRetrimmingActive) return;
  if (e.key === 'Escape') {
    cancelRetrimming();
  } else if (e.key === 'Enter') {
    saveRetrimming();
  }
});

window.startRetrimming = startRetrimming;
window.cancelRetrimming = cancelRetrimming;
window.saveRetrimming = saveRetrimming;
