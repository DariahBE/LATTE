/**
 * State-machine controller for entity disambiguation, Wikidata Q-ID resolution,
 * entity creation, and in-text occurrence propagation (UC1-UC6).
 */
(function (global) {
  'use strict';

  const { el } = global.LinkingDom;
  const api = global.LinkingApi;

  const session = {
    active: false,
    mode: 'idle', // 'unannotated' | 'automated' | 'linked'
    step: 'lookup', // 'lookup' | 'wd_confirm_existing' | 'create_entity'
    start: null,
    stop: null,
    text: '',
    suggestionId: null,
    detectedType: null,
    autoNeoId: 0,
    chosenQID: null,
    dbMatches: [],
    selectedType: ''
  };

  function getTextContentString() {
    const container = document.getElementById('textcontent');
    if (!container) return '';
    const spans = container.querySelectorAll('.ltr');
    if (spans.length === 0) return container.textContent || '';
    let out = '';
    spans.forEach(s => { out += s.textContent; });
    return out;
  }

  function countUnlinkedOccurrencesInDom(needle, matchCase = true, wholeWord = true) {
    const cleanNeedle = String(needle || '').trim();
    if (!cleanNeedle) return 0;

    const spans = document.querySelectorAll('#textcontent .ltr');
    if (!spans.length) return 0;

    const chars = [];
    const blocked = [];
    spans.forEach(s => {
      chars.push(s.textContent);
      const ann = s.getAttribute('data-annotation') || '';
      const isConfirmed = s.classList.contains('linked') && !s.classList.contains('app_automatic') && !ann.includes('latte_sug_');
      blocked.push(isConfirmed);
    });

    const nChars = Array.from(cleanNeedle);
    const nLen = nChars.length;
    const lowerNeedle = cleanNeedle.toLowerCase();
    let count = 0;

    for (let i = 0; i <= chars.length - nLen; i++) {
      let slice = '';
      let isBlocked = false;
      for (let j = 0; j < nLen; j++) {
        if (blocked[i + j]) {
          isBlocked = true;
          break;
        }
        slice += chars[i + j];
      }
      if (isBlocked) continue;

      const matched = matchCase ? (slice === cleanNeedle) : (slice.toLowerCase() === lowerNeedle);
      if (!matched) continue;

      if (wholeWord) {
        if (i > 0 && !/[\s\p{P}]/u.test(chars[i - 1])) continue;
        if (i + nLen < chars.length && !/[\s\p{P}]/u.test(chars[i + nLen])) continue;
      }
      count++;
    }
    return count;
  }

  function syncLegacyGlobals() {
    global.globalSelectionText = session.text;
    global.globalSelectionStart = session.start;
    global.globalSelectionEnd = session.stop;
    global.chosenQID = session.chosenQID;
    global.currentLatteSuggestionId = session.suggestionId;
    global.auto_annotation_internal_id = session.autoNeoId || 0;
  }

  function removeStuckPopup() {
    const box = document.getElementById('suggestionOnSelect');
    if (box) box.remove();
  }

  function getSlideoverTarget() {
    return document.getElementById('slideoverDynamicContent');
  }

  function ensureSlideoverOpen() {
    if (typeof global.toggleSlide === 'function') {
      global.toggleSlide(1);
    }
    if (typeof global.createSideSkelleton === 'function') {
      global.createSideSkelleton();
    }
  }

  function extractEntityDisplayName(nodeItem) {
    const props = nodeItem[2] || {};
    for (const [k, v] of Object.entries(props)) {
      if (v && v.value && k !== 'uid') {
        return String(v.value);
      }
    }
    return '#' + nodeItem[0];
  }

  function applyLinkedHighlightToClientState(annoUuid, type, start, stop) {
    if (Array.isArray(global.automatic_annotations)) {
      global.automatic_annotations = global.automatic_annotations.filter(a => {
        const sStart = parseInt(a.start !== undefined ? a.start : a.startPos, 10);
        const sStop = parseInt(a.stop !== undefined ? a.stop : a.endPos, 10);
        return (sStop < start || sStart > stop);
      });
      if (typeof global.updateAutomaticAnnotationBadges === 'function') {
        global.updateAutomaticAnnotationBadges();
      }
    }
    if (global.storedAnnotations && global.storedAnnotations.relations) {
      global.storedAnnotations.relations[annoUuid] = { start, stop, type };
    }
    if (typeof global.displayUpdatedText === 'function') {
      global.displayUpdatedText(type, start, stop, annoUuid);
    }
  }

  /**
   * Lightweight modal dialog for choosing single-mention vs all-occurrences propagation.
   */
  function openPropagationModal({ entityLabel, selectionText, initialCount, onConfirm, singleOnlyLabel = 'Link only this mention' }) {
    const existing = document.getElementById('linkingPropagationModal');
    if (existing) existing.remove();

    let matchCase = true;
    let wholeWord = true;

    const countBadge = el('span', { className: 'font-bold text-indigo-700' }, String(initialCount));
    const refreshCount = () => {
      const c = countUnlinkedOccurrencesInDom(selectionText, matchCase, wholeWord);
      countBadge.textContent = String(c);
    };

    const caseCb = el('input', {
      type: 'checkbox',
      checked: true,
      className: 'rounded border-slate-300 text-indigo-600 focus:ring-indigo-500',
      onchange: (e) => { matchCase = e.target.checked; refreshCount(); }
    });
    const wordCb = el('input', {
      type: 'checkbox',
      checked: true,
      className: 'rounded border-slate-300 text-indigo-600 focus:ring-indigo-500',
      onchange: (e) => { wholeWord = e.target.checked; refreshCount(); }
    });

    const close = () => {
      const m = document.getElementById('linkingPropagationModal');
      if (m) m.remove();
    };

    const modal = el('div', {
      id: 'linkingPropagationModal',
      className: 'fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/50 p-4'
    }, [
      el('div', { className: 'bg-white border border-slate-200 rounded-2xl shadow-xl w-full max-w-md overflow-hidden' }, [
        el('div', { className: 'px-5 py-4 border-b border-slate-100 flex items-center justify-between' }, [
          el('h3', { className: 'text-sm font-bold text-slate-900 flex items-center gap-2' }, [
            el('i', { className: 'fas fa-clone text-indigo-600' }),
            'Propagate to identical mentions?'
          ]),
          el('button', {
            type: 'button',
            className: 'text-slate-400 hover:text-slate-700 cursor-pointer',
            onclick: close
          }, [el('i', { className: 'fas fa-times' })])
        ]),
        el('div', { className: 'p-5 space-y-4 text-xs text-slate-600' }, [
          el('p', {}, [
            'Found ',
            countBadge,
            ' unlinked occurrence(s) of ',
            el('strong', { className: 'text-slate-900' }, `"${selectionText}"`),
            ' in this document for ',
            el('strong', { className: 'text-indigo-700' }, entityLabel),
            '.'
          ]),
          el('div', { className: 'p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2' }, [
            el('label', { className: 'flex items-center gap-2 cursor-pointer text-slate-700 font-medium' }, [
              caseCb,
              'Case-sensitive match'
            ]),
            el('label', { className: 'flex items-center gap-2 cursor-pointer text-slate-700 font-medium' }, [
              wordCb,
              'Whole-word boundaries only'
            ])
          ]),
          el('div', { className: 'flex flex-wrap items-center justify-end gap-2 pt-2' }, [
            el('button', {
              type: 'button',
              className: 'px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold cursor-pointer',
              onclick: () => { close(); onConfirm({ propagate: false, matchCase, wholeWord }); }
            }, singleOnlyLabel),
            el('button', {
              type: 'button',
              className: 'px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm cursor-pointer',
              onclick: () => { close(); onConfirm({ propagate: true, matchCase, wholeWord }); }
            }, 'Link all occurrences')
          ])
        ])
      ])
    ]);

    document.body.appendChild(modal);
  }

  async function executeLinkExisting(entityNeoId, entityLabel, propagateOpts) {
    const texNeoId = api.getTextNeoId();
    const res = await api.linkExistingEntity(
      entityNeoId,
      session.start,
      session.stop,
      session.text,
      texNeoId,
      propagateOpts
    );

    const primary = res.primary;
    applyLinkedHighlightToClientState(primary.annotation, primary.type, primary.start, primary.stop);

    let extraCount = 0;
    if (res.propagated && Array.isArray(res.propagated.annotations)) {
      extraCount = res.propagated.created_count || 0;
      res.propagated.annotations.forEach(a => {
        applyLinkedHighlightToClientState(a.uuid, a.type, a.start, a.stop);
      });
    }

    session.active = false;
    session.mode = 'idle';

    if (typeof global.showToast === 'function') {
      const msg = extraCount > 0
        ? `Linked "${session.text}" to ${entityLabel} and propagated to ${extraCount} additional occurrence(s).`
        : `Linked "${session.text}" to ${entityLabel}.`;
      global.showToast(msg, 'success');
    }

    if (typeof global.loadAnnotationData === 'function') {
      global.loadAnnotationData(primary.annotation);
    }
  }

  function handleLinkExistingClick(entityNeoId, entityLabel, forcePropagate = false) {
    const dupCount = countUnlinkedOccurrencesInDom(session.text, true, true);
    if (forcePropagate) {
      executeLinkExisting(entityNeoId, entityLabel, { propagate: true, matchCase: true, wholeWord: true })
        .catch(err => {
          if (typeof global.showToast === 'function') global.showToast(err.message, 'error');
        });
      return;
    }
    if (dupCount > 1) {
      openPropagationModal({
        entityLabel,
        selectionText: session.text,
        initialCount: dupCount,
        onConfirm: (opts) => {
          executeLinkExisting(entityNeoId, entityLabel, opts).catch(err => {
            if (typeof global.showToast === 'function') global.showToast(err.message, 'error');
          });
        }
      });
    } else {
      executeLinkExisting(entityNeoId, entityLabel, { propagate: false }).catch(err => {
        if (typeof global.showToast === 'function') global.showToast(err.message, 'error');
      });
    }
  }

  function renderSelectionBanner(target) {
    const badges = [];
    if (session.mode === 'automated' && session.detectedType) {
      badges.push(el('span', {
        className: 'px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200'
      }, 'LATTE: ' + session.detectedType));
    }
    if (session.chosenQID) {
      badges.push(el('span', {
        className: 'px-2 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-indigo-100 text-indigo-700 border border-indigo-200'
      }, session.chosenQID));
    }

    const headerActions = [];
    if (session.mode === 'automated' && session.suggestionId) {
      const sugId = session.suggestionId;
      const sugStart = session.start;
      const sugStop = session.stop;
      const sugLabel = session.detectedType || 'Entity';

      headerActions.push(el('button', {
        type: 'button',
        className: 'inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition cursor-pointer shadow-2xs',
        title: 'Adjust the boundary handles of this selection',
        onclick: () => {
          if (typeof global.startRetrimming === 'function') {
            global.startRetrimming(sugId, sugStart, sugStop, sugLabel);
          }
        }
      }, [el('i', { className: 'fas fa-arrows-alt-h text-[10px]' }), el('span', {}, 'Retrim')]));

      headerActions.push(el('button', {
        type: 'button',
        className: 'inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition cursor-pointer shadow-2xs',
        title: 'Delete this automatic suggestion',
        onclick: () => {
          if (typeof global.removeAutomaticSuggestion === 'function') {
            global.removeAutomaticSuggestion(sugId);
          }
        }
      }, [el('i', { className: 'fas fa-trash-alt text-[10px]' }), el('span', {}, 'Delete')]));
    }

    target.appendChild(el('div', { className: 'mb-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2' }, [
      el('div', { className: 'flex flex-wrap items-center justify-between gap-2' }, [
        el('div', { className: 'flex flex-wrap items-center gap-2' }, [
          el('span', { className: 'text-[11px] font-bold uppercase tracking-wider text-slate-400' }, 'Selected mention'),
          ...badges
        ]),
        el('div', { className: 'flex items-center gap-1.5' }, headerActions)
      ]),
      el('div', { className: 'text-sm font-bold text-slate-900 break-words' }, `"${session.text}"`),
      el('div', { className: 'text-[11px] font-mono text-slate-500' }, `Offsets: ${session.start} - ${session.stop}`)
    ]));
  }

  function renderDbEntityRow(nodeItem) {
    const eid = nodeItem[0];
    const etype = nodeItem[1] || 'Entity';
    const label = extractEntityDisplayName(nodeItem);
    const cooccurs = Boolean(nodeItem.context_boost);

    return el('div', { className: 'p-3 rounded-xl bg-white border border-slate-200 flex items-center justify-between gap-3 shadow-2xs' }, [
      el('div', { className: 'min-w-0 flex-1' }, [
        el('div', { className: 'flex items-center gap-2 flex-wrap' }, [
          el('span', { className: 'text-sm font-bold text-slate-900 truncate' }, label),
          el('span', { className: 'px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200' }, etype),
          cooccurs ? el('span', { className: 'px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200' }, 'In this text') : null
        ])
      ]),
      el('div', { className: 'flex items-center gap-1.5 flex-shrink-0' }, [
        el('button', {
          type: 'button',
          className: 'px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold cursor-pointer',
          onclick: () => handleLinkExistingClick(eid, `${etype}: ${label}`, false)
        }, 'Link'),
        el('button', {
          type: 'button',
          className: 'px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold cursor-pointer',
          title: 'Link this mention and all identical unlinked occurrences',
          onclick: () => handleLinkExistingClick(eid, `${etype}: ${label}`, true)
        }, 'Link all')
      ])
    ]);
  }

  /**
   * Step 1: DB Variant matches + Wikidata search + "Don't link" (no entity type dropdown yet).
   */
  function renderLookupStep() {
    session.step = 'lookup';
    const target = getSlideoverTarget();
    if (!target) return;
    target.innerHTML = '';

    renderSelectionBanner(target);

    // 1. Existing DB matches.
    const dbSection = el('div', { className: 'mb-5 space-y-2' });
    dbSection.appendChild(el('h3', { className: 'text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5' }, [
      el('i', { className: 'fas fa-database text-indigo-600' }),
      `Matching database entities (${session.dbMatches.length})`
    ]));

    if (session.dbMatches.length === 0) {
      dbSection.appendChild(el('p', { className: 'text-xs text-slate-500 italic bg-white border border-slate-200 rounded-xl p-3' },
        'No existing entity matches this spelling yet. Search another spelling below, link via Wikidata, or create a custom entity.'));
    } else {
      const list = el('div', { className: 'space-y-2' });
      session.dbMatches.forEach(nodeItem => list.appendChild(renderDbEntityRow(nodeItem)));
      dbSection.appendChild(list);
    }

    // Manual database entity search for linking alternate spellings without a Wikidata Q-ID.
    const availableTypes = Array.isArray(global.coreNodes) ? global.coreNodes : [];
    const dbSearchType = el('select', {
      id: 'dbEntitySearchType',
      className: 'px-2 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
    }, [
      el('option', { value: '' }, 'All types'),
      ...availableTypes.map(t => el('option', { value: t }, t))
    ]);

    const dbSearchInput = el('input', {
      type: 'text',
      id: 'dbEntitySearchInput',
      placeholder: 'Search existing entities by another spelling...',
      className: 'flex-1 min-w-0 px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
    });

    const dbManualResults = el('div', { id: 'dbManualSearchResults', className: 'space-y-2' });

    const runManualDbSearch = async () => {
      const q = dbSearchInput.value.trim();
      if (!q) {
        dbManualResults.innerHTML = '';
        return;
      }
      dbManualResults.innerHTML = '';
      dbManualResults.appendChild(el('div', { className: 'text-xs text-slate-500 p-2 flex items-center gap-2' }, [
        el('i', { className: 'fas fa-spinner fa-spin text-indigo-600' }),
        `Searching database for "${q}"...`
      ]));

      try {
        const res = await api.fetchEntitySuggestions(q, api.getTextNeoId(), {
          type: dbSearchType.value,
          caseSensitive: false,
          allowLevenshtein: false
        });
        const hits = (res && Array.isArray(res.nodes)) ? res.nodes : [];
        dbManualResults.innerHTML = '';
        if (hits.length === 0) {
          dbManualResults.appendChild(el('p', { className: 'text-xs text-slate-500 italic p-2' },
            `No database entities matched "${q}".`));
          return;
        }
        hits.forEach(nodeItem => dbManualResults.appendChild(renderDbEntityRow(nodeItem)));
      } catch (err) {
        dbManualResults.innerHTML = '';
        dbManualResults.appendChild(el('p', { className: 'text-xs text-rose-600 p-2' }, err.message || 'Database search failed.'));
      }
    };

    const dbSearchForm = el('form', {
      className: 'flex flex-wrap items-center gap-1.5 pt-2',
      onsubmit: (e) => {
        e.preventDefault();
        runManualDbSearch();
      }
    }, [
      dbSearchInput,
      dbSearchType,
      el('button', {
        type: 'button',
        id: 'dbEntitySearchBtn',
        className: 'px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-2xs cursor-pointer',
        onclick: () => runManualDbSearch()
      }, [el('i', { className: 'fas fa-search mr-1' }), 'Search'])
    ]);

    dbSection.appendChild(el('div', { className: 'pt-2 border-t border-slate-100 space-y-2' }, [
      el('div', { className: 'text-[11px] font-semibold text-slate-500' }, 'Link to an existing entity under a different spelling'),
      dbSearchForm,
      dbManualResults
    ]));

    target.appendChild(dbSection);

    // 2. Wikidata search + Don't link button.
    const wdSearchInput = el('input', {
      type: 'text',
      id: 'wikidataSearchInput',
      value: session.text,
      placeholder: 'Search Wikidata...',
      className: 'flex-1 min-w-0 px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
      onkeydown: (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          const q = wdSearchInput.value.trim();
          if (q && typeof global.wdprompt === 'function') global.wdprompt(q, 0);
        }
      }
    });

    const wdSearchForm = el('form', {
      className: 'flex items-center gap-2',
      onsubmit: (e) => {
        e.preventDefault();
        const q = wdSearchInput.value.trim();
        if (q && typeof global.wdprompt === 'function') global.wdprompt(q, 0);
      }
    }, [
      wdSearchInput,
      el('button', {
        type: 'submit',
        id: 'wikidataSearchBtn',
        className: 'px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-2xs cursor-pointer'
      }, [el('i', { className: 'fas fa-search mr-1' }), 'Search'])
    ]);

    const wdSection = el('div', { className: 'space-y-3 border-t border-slate-200 pt-4' }, [
      el('div', { className: 'flex items-center justify-between gap-2' }, [
        el('h3', { className: 'text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5' }, [
          el('i', { className: 'fas fa-globe text-indigo-600' }),
          'Link to Wikidata or create new'
        ]),
        el('button', {
          type: 'button',
          id: 'dontLinkEntityBtn',
          className: 'px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 text-xs font-semibold cursor-pointer',
          onclick: () => {
            session.chosenQID = null;
            syncLegacyGlobals();
            renderEntityTypeStep();
          }
        }, "Don't link (create new)")
      ]),
      wdSearchForm,
      el('div', { id: 'wdpromptBox' })
    ]);
    target.appendChild(wdSection);

    if (typeof global.wdprompt === 'function') {
      global.wdprompt(session.text, 0);
    }
  }

  /**
   * Step 1b: Compact confirmation card when an accepted Wikidata Q-ID already exists in Neo4j.
   */
  async function renderQidConfirmationStep(qid, hitIds) {
    session.step = 'wd_confirm_existing';
    const target = getSlideoverTarget();
    if (!target) return;
    target.innerHTML = '';

    renderSelectionBanner(target);

    const card = el('div', { className: 'p-4 rounded-xl bg-indigo-50/70 border border-indigo-200 space-y-3' }, [
      el('div', { className: 'flex items-center gap-2 text-sm font-bold text-indigo-900' }, [
        el('i', { className: 'fas fa-check-circle text-indigo-600' }),
        `Existing entity found for ${qid}`
      ]),
      el('p', { className: 'text-xs text-slate-600' },
        `An entity in the database is already linked to ${qid}. Link this mention directly (adding "${session.text}" as a spelling variant), or create a separate entity instead.`),
      el('div', { id: 'qidExistingMatchList', className: 'space-y-2' }, [
        el('div', { className: 'text-xs text-slate-500' }, 'Loading entity details...')
      ]),
      el('div', { className: 'pt-2 border-t border-indigo-200/70 flex flex-wrap items-center justify-between gap-2' }, [
        el('button', {
          type: 'button',
          className: 'text-xs font-semibold text-slate-600 hover:text-slate-900 underline cursor-pointer',
          onclick: () => renderLookupStep()
        }, 'Back to lookup'),
        el('button', {
          type: 'button',
          className: 'px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold cursor-pointer',
          onclick: () => renderEntityTypeStep(true)
        }, 'Create separate entity instead')
      ])
    ]);
    target.appendChild(card);

    const listEl = document.getElementById('qidExistingMatchList');
    try {
      const details = await Promise.all(hitIds.map(id => api.fetchEntityById(id).then(d => ({ id, data: d }))));
      listEl.innerHTML = '';
      details.forEach(({ id, data }) => {
        const label = (data && data.extra && data.extra.label) ? data.extra.label : 'Entity';
        const props = (data && data.props) ? data.props : {};
        let title = '';
        Object.entries(props).forEach(([k, v]) => {
          if (!title && k !== 'uid' && v && v.value) title = String(v.value);
        });
        if (!title) title = '#' + id;

        listEl.appendChild(el('div', { className: 'p-3 rounded-xl bg-white border border-indigo-100 flex items-center justify-between gap-3' }, [
          el('div', { className: 'min-w-0 flex-1' }, [
            el('div', { className: 'text-sm font-bold text-slate-900 truncate' }, title),
            el('div', { className: 'text-[11px] text-slate-500' }, `${label} • ${qid}`)
          ]),
          el('div', { className: 'flex items-center gap-1.5 flex-shrink-0' }, [
            el('button', {
              type: 'button',
              className: 'px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold cursor-pointer',
              onclick: () => handleLinkExistingClick(id, `${label}: ${title}`, false)
            }, 'Link & add variant'),
            el('button', {
              type: 'button',
              className: 'px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold cursor-pointer',
              onclick: () => handleLinkExistingClick(id, `${label}: ${title}`, true)
            }, 'Link all occurrences')
          ])
        ]));
      });
    } catch (err) {
      listEl.innerHTML = '';
      listEl.appendChild(el('div', { className: 'text-xs text-rose-600' }, err.message));
    }
  }

  /**
   * Step 2: Set entity type (preselecting LATTE detected_type if valid) + property inputs + Save.
   */
  function renderEntityTypeStep(forceSplitQid = false) {
    session.step = 'create_entity';
    const target = getSlideoverTarget();
    if (!target) return;
    target.innerHTML = '';

    renderSelectionBanner(target);

    const availableTypes = (typeof coreNodes !== 'undefined' && Array.isArray(coreNodes))
      ? coreNodes.filter(t => t !== 'Annotation' && t !== 'Text' && t !== 'Variant' && t !== 'See_Also')
      : [];

    // Preselect LATTE detected_type when valid in NODEMODEL.
    let preselected = '';
    if (session.detectedType) {
      const match = availableTypes.find(t => t.toLowerCase() === String(session.detectedType).toLowerCase());
      if (match) preselected = match;
    }

    const typeSelect = el('select', {
      id: 'entityTypeSelector',
      className: 'w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
      onchange: () => loadPropertiesForType(typeSelect.value, propertyContainer, schemaNoticeEl, saveBtn)
    }, [
      el('option', { value: '' }, '-- Select entity type --'),
      ...availableTypes.map(t => el('option', { value: t, selected: (t === preselected) }, t))
    ]);

    const schemaNoticeEl = el('div', {
      className: 'hidden text-[11px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2'
    }, 'Note: The selected entity type does not define a Wikidata ID field in the schema.');

    const propertyContainer = el('div', { id: 'propertyBox', className: 'space-y-2.5' });
    const statusBox = el('div', { className: 'hidden text-xs font-medium' });

    const saveBtn = el('button', {
      type: 'button',
      id: 'saveEtToDb',
      disabled: true,
      className: 'w-full px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer',
      onclick: () => handleSaveNewEntity(typeSelect.value, propertyContainer, forceSplitQid, statusBox, saveBtn)
    }, 'Save and create entity');

    const formCard = el('div', { className: 'p-4 rounded-xl bg-white border border-slate-200 space-y-4' }, [
      el('div', { className: 'flex items-center justify-between border-b border-slate-100 pb-2.5' }, [
        el('h3', { className: 'text-sm font-bold text-slate-900' }, 'Create new entity'),
        el('button', {
          type: 'button',
          className: 'text-xs font-semibold text-slate-500 hover:text-slate-800 underline cursor-pointer',
          onclick: () => renderLookupStep()
        }, 'Back to lookup')
      ]),
      el('div', { className: 'space-y-1.5' }, [
        el('label', { for: 'entityTypeSelector', className: 'block text-xs font-semibold text-slate-700' }, 'Set entity type'),
        typeSelect
      ]),
      schemaNoticeEl,
      propertyContainer,
      statusBox,
      saveBtn
    ]);

    target.appendChild(formCard);

    if (preselected) {
      loadPropertiesForType(preselected, propertyContainer, schemaNoticeEl, saveBtn);
    }
  }

  async function loadPropertiesForType(typeName, propertyContainer, schemaNoticeEl, saveBtn) {
    session.selectedType = typeName;
    propertyContainer.innerHTML = '';
    schemaNoticeEl.classList.add('hidden');
    saveBtn.disabled = !typeName;
    if (!typeName) return;

    propertyContainer.appendChild(el('div', { className: 'text-xs text-slate-400' }, 'Loading properties...'));
    try {
      const res = await api.fetchEntityStructure(typeName);
      propertyContainer.innerHTML = '';
      if (!res || res.msg !== 'success' || !res.data) {
        propertyContainer.appendChild(el('div', { className: 'text-xs text-rose-600' }, 'Could not load properties for ' + typeName));
        saveBtn.disabled = true;
        return;
      }

      let hasWikidataField = false;
      Object.entries(res.data).forEach(([key, meta]) => {
        const label = meta[0] || key;
        const datatype = String(meta[1] || 'string').toLowerCase();
        const isRequired = Boolean(meta[2]);

        if (datatype === 'wikidata') {
          hasWikidataField = true;
        }

        let inputEl;
        if (datatype === 'bool') {
          inputEl = el('input', {
            type: 'checkbox',
            name: key,
            id: 'prop_' + key,
            className: 'h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500'
          });
        } else {
          let defaultVal = '';
          if (datatype === 'wikidata' && session.chosenQID) {
            defaultVal = session.chosenQID;
          } else if (['name', 'named', 'label', 'value', 'variant', 'title'].includes(key.toLowerCase())) {
            defaultVal = session.text;
          }
          inputEl = el('input', {
            type: datatype === 'int' || datatype === 'float' ? 'number' : 'text',
            name: key,
            id: 'prop_' + key,
            value: defaultVal,
            required: isRequired && datatype !== 'int',
            placeholder: datatype === 'wikidata' ? 'e.g. Q123' : '',
            className: 'w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
          });
          if (datatype === 'wikidata') {
            inputEl.dataset.datatype = 'wikidata';
          }
        }

        propertyContainer.appendChild(el('div', { className: 'space-y-1' }, [
          el('label', { for: 'prop_' + key, className: 'block text-xs font-semibold text-slate-700' }, label),
          inputEl
        ]));
      });

      if (session.chosenQID && !hasWikidataField) {
        schemaNoticeEl.classList.remove('hidden');
      }
    } catch (err) {
      propertyContainer.innerHTML = '';
      propertyContainer.appendChild(el('div', { className: 'text-xs text-rose-600' }, err.message));
      saveBtn.disabled = true;
    }
  }

  async function executeSaveNewEntity(typeName, properties, forceSplitQid, propagateOpts) {
    const startKey = (typeof startcode !== 'undefined') ? startcode : 'starts';
    const stopKey = (typeof stopcode !== 'undefined') ? stopcode : 'stops';

    const payload = {
      texid: api.getTextNeoId(),
      nodetype: typeName,
      annotation: {
        [startKey]: session.start,
        [stopKey]: session.stop
      },
      variants: [session.text],
      properties,
      wikidata_qid: session.chosenQID || '',
      split_wikidata_entity: forceSplitQid ? 'true' : 'false',
      annotationmode: session.mode === 'automated' ? 'automated' : '',
      neo_id_internal: session.autoNeoId || 0,
      propagate: propagateOpts && propagateOpts.propagate ? 'true' : 'false',
      case_sensitive: propagateOpts && propagateOpts.matchCase === false ? 'false' : 'true',
      whole_word: propagateOpts && propagateOpts.wholeWord === false ? 'false' : 'true'
    };

    const res = await api.saveAnnotationAndEntity(payload);
    const putRs = res.data;
    applyLinkedHighlightToClientState(putRs.uuid, putRs.type, putRs.start, putRs.stop);

    let extraCount = 0;
    if (Array.isArray(putRs.propagated)) {
      extraCount = putRs.propagated.length;
      putRs.propagated.forEach(p => {
        applyLinkedHighlightToClientState(p.uuid, p.type, p.start, p.stop);
      });
    }

    session.active = false;
    session.mode = 'idle';

    if (typeof global.showToast === 'function') {
      const baseMsg = putRs.merged_into_existing
        ? `Linked as spelling variant of existing ${putRs.type}.`
        : `Created ${putRs.type} and linked annotation.`;
      const fullMsg = extraCount > 0 ? `${baseMsg} Propagated to ${extraCount} additional occurrence(s).` : baseMsg;
      global.showToast(fullMsg, 'success');
    }

    if (typeof global.loadAnnotationData === 'function') {
      global.loadAnnotationData(putRs.uuid);
    }
  }

  function handleSaveNewEntity(typeName, propertyContainer, forceSplitQid, statusBox, saveBtn) {
    if (!typeName) return;
    const properties = {};
    propertyContainer.querySelectorAll('input, select, textarea').forEach(inp => {
      if (!inp.name) return;
      properties[inp.name] = inp.type === 'checkbox' ? inp.checked : inp.value;
    });

    const dupCount = countUnlinkedOccurrencesInDom(session.text, true, true);
    const runSave = (opts) => {
      saveBtn.disabled = true;
      statusBox.classList.add('hidden');
      executeSaveNewEntity(typeName, properties, forceSplitQid, opts).catch(err => {
        saveBtn.disabled = false;
        statusBox.className = 'text-xs font-semibold text-rose-600';
        statusBox.textContent = err.message;
        statusBox.classList.remove('hidden');
      });
    };

    if (dupCount > 1) {
      openPropagationModal({
        entityLabel: typeName,
        selectionText: session.text,
        initialCount: dupCount,
        singleOnlyLabel: 'Save for this mention only',
        onConfirm: runSave
      });
    } else {
      runSave({ propagate: false });
    }
  }

  const LinkingController = {
    isActive() {
      return session.active;
    },

    reset() {
      session.active = false;
      session.mode = 'idle';
      session.step = 'lookup';
      session.start = null;
      session.stop = null;
      session.text = '';
      session.suggestionId = null;
      session.detectedType = null;
      session.autoNeoId = 0;
      session.chosenQID = null;
      session.dbMatches = [];
      session.selectedType = '';
      syncLegacyGlobals();
    },

    async openForSelection(rawText, start, stop) {
      const cleanText = String(rawText || '').trim();
      if (!cleanText) return;

      removeStuckPopup();
      session.active = true;
      session.mode = 'unannotated';
      session.step = 'lookup';
      session.start = parseInt(start, 10);
      session.stop = parseInt(stop, 10);
      session.text = cleanText;
      session.suggestionId = null;
      session.detectedType = null;
      session.autoNeoId = 0;
      session.chosenQID = null;
      session.dbMatches = [];
      syncLegacyGlobals();

      ensureSlideoverOpen();
      const target = getSlideoverTarget();
      if (target) {
        target.innerHTML = '';
        renderSelectionBanner(target);
        target.appendChild(el('div', { className: 'p-4 text-xs text-slate-500 flex items-center gap-2' }, [
          el('i', { className: 'fas fa-spinner fa-spin text-indigo-600' }),
          'Checking database for matching entities...'
        ]));
      }

      try {
        const res = await api.fetchEntitySuggestions(cleanText, api.getTextNeoId());
        session.dbMatches = (res && Array.isArray(res.nodes)) ? res.nodes : [];
      } catch (err) {
        session.dbMatches = [];
      }
      renderLookupStep();
    },

    async openForAutomaticSuggestion(data, start, stop, rawText, suggestionId, detectedType) {
      const cleanText = String(rawText || '').trim();
      if (!cleanText) return;

      session.active = true;
      session.mode = 'automated';
      session.step = 'lookup';
      session.start = parseInt(start, 10);
      session.stop = parseInt(stop, 10);
      session.text = cleanText;
      session.suggestionId = suggestionId || null;
      session.detectedType = detectedType || null;
      session.autoNeoId = (data && data.neo_id_of_auto_anno) ? parseInt(data.neo_id_of_auto_anno, 10) : 0;
      session.chosenQID = null;
      session.dbMatches = [];
      syncLegacyGlobals();

      ensureSlideoverOpen();
      const target = getSlideoverTarget();
      if (target) {
        target.innerHTML = '';
        renderSelectionBanner(target);
        target.appendChild(el('div', { className: 'p-4 text-xs text-slate-500 flex items-center gap-2' }, [
          el('i', { className: 'fas fa-spinner fa-spin text-indigo-600' }),
          'Checking database for matching entities...'
        ]));
      }

      try {
        const res = await api.fetchEntitySuggestions(cleanText, api.getTextNeoId());
        session.dbMatches = (res && Array.isArray(res.nodes)) ? res.nodes : [];
      } catch (err) {
        session.dbMatches = [];
      }
      renderLookupStep();
    },

    onWikidataPicked(qid) {
      session.chosenQID = qid || null;
      syncLegacyGlobals();
    },

    async onWikidataAccepted(qid) {
      session.chosenQID = (qid && qid !== -1) ? String(qid) : null;
      syncLegacyGlobals();

      if (!session.chosenQID) {
        renderEntityTypeStep(false);
        return;
      }

      try {
        const res = await api.checkWikidataExists(session.chosenQID);
        const hits = (res && Array.isArray(res.data)) ? res.data : [];
        if (hits.length > 0) {
          await renderQidConfirmationStep(session.chosenQID, hits);
          return;
        }
      } catch (err) {
        console.warn('Wikidata check error:', err);
      }
      renderEntityTypeStep(false);
    },

    openPropagationForConfirmed(entityNeoId, entityLabel, selectionText) {
      const dupCount = countUnlinkedOccurrencesInDom(selectionText, true, true);
      openPropagationModal({
        entityLabel,
        selectionText,
        initialCount: dupCount,
        singleOnlyLabel: 'Cancel',
        onConfirm: (opts) => {
          if (!opts.propagate) return;
          api.propagateEntityOccurrences(entityNeoId, selectionText, api.getTextNeoId(), opts)
            .then(data => {
              if (data.success && Array.isArray(data.annotations)) {
                data.annotations.forEach(a => {
                  applyLinkedHighlightToClientState(a.uuid, a.type, a.start, a.stop);
                });
                if (typeof global.showToast === 'function') {
                  global.showToast(data.msg || `Linked ${data.created_count || 0} occurrence(s).`, 'success');
                }
              }
            })
            .catch(err => {
              if (typeof global.showToast === 'function') global.showToast(err.message, 'error');
            });
        }
      });
    }
  };

  global.LinkingController = LinkingController;
})(window);
