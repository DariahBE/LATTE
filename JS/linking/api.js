/**
 * Unified async/await API client for entity linking, Wikidata checks, and propagation.
 */
(function (global) {
  'use strict';

  function getTextNeoId() {
    if (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.nodeid) {
      return languageOptions.nodeid;
    }
    return global.currentTextNeoId || 0;
  }

  async function fetchEntitySuggestions(text, texNeoId, options = {}) {
    const params = new URLSearchParams({
      type: options.type || '',
      value: String(text || '').trim(),
      casesensitive: options.caseSensitive ? 'true' : 'false',
      allow_levenshtein: options.allowLevenshtein ? 'true' : 'false',
      levenshtein_items: String(options.levenshteinItems || 5),
      texid: String(texNeoId || getTextNeoId() || '')
    });
    const res = await fetch('/AJAX/getEntitySuggestion.php?' + params.toString());
    if (!res.ok) throw new Error('Entity suggestion lookup failed (' + res.status + ')');
    return res.json();
  }

  async function checkWikidataExists(qid) {
    const res = await fetch('/AJAX/checkWDExists.php?qid=' + encodeURIComponent(qid));
    if (!res.ok) throw new Error('Wikidata check failed (' + res.status + ')');
    return res.json();
  }

  async function fetchEntityById(neoId) {
    const res = await fetch('/AJAX/getETById.php?id=' + encodeURIComponent(neoId) + '&extended=1');
    if (!res.ok) throw new Error('Entity lookup failed (' + res.status + ')');
    return res.json();
  }

  async function fetchEntityStructure(type) {
    const res = await fetch('/AJAX/get_structure.php?type=' + encodeURIComponent(type));
    if (!res.ok) throw new Error('Schema lookup failed (' + res.status + ')');
    return res.json();
  }

  async function fetchDisposableToken() {
    const res = await fetch('/AJAX/getdisposabletoken.php?task=1');
    if (!res.ok) throw new Error('CSRF token fetch failed (' + res.status + ')');
    return res.json();
  }

  async function fetchFastConnectToken() {
    const res = await fetch('/user/AJAX/profilestate.php?fastconnect=1');
    if (!res.ok) throw new Error('Session token fetch failed (' + res.status + ')');
    const data = await res.json();
    return (data && data.valid && data.csrf) ? data.csrf : null;
  }

  function appendNestedFormData(formData, obj, prefix) {
    Object.entries(obj).forEach(([k, v]) => {
      const key = prefix ? `${prefix}[${k}]` : k;
      if (v !== null && typeof v === 'object' && !(v instanceof File)) {
        appendNestedFormData(formData, v, key);
      } else {
        formData.append(key, v !== null && v !== undefined ? String(v) : '');
      }
    });
  }

  async function saveAnnotationAndEntity(payload) {
    const token = await fetchDisposableToken();
    const fullPayload = Object.assign({}, payload, { token });
    const formData = new FormData();
    appendNestedFormData(formData, fullPayload, 'data');

    const res = await fetch('/AJAX/put_annotation.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (!res.ok || !data || !data.data) {
      throw new Error((data && (data.msg || data.error || data.ERR)) || 'Failed to save annotation.');
    }
    return data;
  }

  async function linkExistingEntity(entityId, start, stop, text, texNeoId, propagateOptions = null) {
    const fastToken = await fetchFastConnectToken();
    if (!fastToken) {
      throw new Error('Authentication required to link entities.');
    }

    const formData = new FormData();
    formData.append('sourceNeoID', String(entityId));
    formData.append('texNeoid', String(texNeoId || getTextNeoId()));
    formData.append('csrf', fastToken);
    formData.append('start', String(start));
    formData.append('stop', String(stop));
    formData.append('selection', String(text || ''));

    const res = await fetch('/AJAX/crud/connect.php', {
      method: 'POST',
      body: formData
    });
    const connectData = await res.json();
    if (!connectData || typeof connectData !== 'object' || !connectData.annotation) {
      const errMsg = typeof connectData === 'string'
        ? connectData
        : ((connectData && (connectData.msg || connectData.error)) || 'Failed to link entity.');
      throw new Error(errMsg);
    }

    let propagated = null;
    if (propagateOptions && propagateOptions.propagate) {
      propagated = await propagateEntityOccurrences(entityId, text, texNeoId, propagateOptions);
    }

    return { primary: connectData, propagated };
  }

  async function propagateEntityOccurrences(entityId, text, texNeoId, options = {}) {
    const csrf = await fetchDisposableToken();
    const batchForm = new FormData();
    batchForm.append('sourceNeoID', String(entityId));
    batchForm.append('texNeoid', String(texNeoId || getTextNeoId()));
    batchForm.append('selection', String(text || ''));
    batchForm.append('csrf', csrf);
    batchForm.append('matchCase', options.matchCase === false ? 'false' : 'true');
    batchForm.append('wholeWord', options.wholeWord === false ? 'false' : 'true');

    const res = await fetch('/AJAX/crud/connect_all_occurrences.php', {
      method: 'POST',
      body: batchForm
    });
    const data = await res.json();
    if (!res.ok || (data && data.error)) {
      throw new Error((data && data.error) || 'Batch propagation failed.');
    }
    return data;
  }

  global.LinkingApi = {
    getTextNeoId,
    fetchEntitySuggestions,
    checkWikidataExists,
    fetchEntityById,
    fetchEntityStructure,
    saveAnnotationAndEntity,
    linkExistingEntity,
    propagateEntityOccurrences
  };
})(window);
