var allNodes = [];
var allEdges = [];
var network = null;
var optionColorRecovery = {};
var excludeNodeIdFromNextXHR = [];
var hiddenNodeTypes = {};
var isPhysicsEnabled = true;

// default color definitions fallback when config colors are missing
var defaultColors = {
  Person: 'rgba(39, 123, 245, 0.75)',
  Place: 'rgba(245, 178, 39, 0.75)',
  Text: 'rgba(28, 200, 28, 0.75)',
  Annotation: 'rgba(200, 28, 28, 0.75)',
  Annotation_auto: 'rgba(156, 163, 175, 0.75)',
  Organization: 'rgba(145, 100, 52, 0.75)',
  Dog: 'rgba(200, 20, 200, 0.75)',
  Test: 'rgba(200, 200, 20, 0.75)'
};

function colorLookup(label) {
  if (window.colorDefinitions && window.colorDefinitions[label]) {
    return window.colorDefinitions[label];
  }
  return defaultColors[label] || 'rgba(100, 116, 139, 0.75)';
}

function labelLookup(label) {
  if (window.nodeTranslations && window.nodeTranslations[label]) {
    return window.nodeTranslations[label];
  }
  return label;
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function zoom(to) {
  if (!network) return;
  switch (to) {
    case 'in':
      var currentScale = network.getScale();
      network.moveTo({
        scale: currentScale * 1.25,
        animation: { duration: 250, easingFunction: 'easeInOutQuad' }
      });
      break;
    case 'out':
      var scaleOut = network.getScale();
      network.moveTo({
        scale: scaleOut * 0.8,
        animation: { duration: 250, easingFunction: 'easeInOutQuad' }
      });
      break;
    default:
      network.fit({
        animation: { duration: 400, easingFunction: 'easeInOutQuad' }
      });
      break;
  }
}

function toggleLayoutAlgo(model) {
  if (!network) return;
  network.setOptions({ physics: { solver: model } });
  network.stabilize(60);
}

function togglePhysics(forceState) {
  if (!network) return;
  isPhysicsEnabled = typeof forceState === 'boolean' ? forceState : !isPhysicsEnabled;
  network.setOptions({ physics: { enabled: isPhysicsEnabled } });

  var icon = document.getElementById('physics-icon');
  var btn = document.getElementById('btn-physics-toggle');
  if (icon && btn) {
    if (isPhysicsEnabled) {
      icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />';
      btn.title = 'Pause physics simulation (Space)';
      btn.classList.remove('text-amber-600', 'bg-amber-50');
    } else {
      icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
      btn.title = 'Resume physics simulation (Space)';
      btn.classList.add('text-amber-600', 'bg-amber-50');
    }
  }
}

function toggleFullscreen() {
  var icon = document.getElementById('fullscreen-icon');
  if (!document.fullscreenElement) {
    var elem = document.getElementById('explorer-viewport') || document.documentElement;
    if (elem.requestFullscreen) {
      elem.requestFullscreen().catch(function(err) {
        console.warn('Could not enter fullscreen:', err);
      });
    }
    if (icon) {
      icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().catch(function(err) {
        console.warn('Could not exit fullscreen:', err);
      });
    }
    if (icon) {
      icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>';
    }
  }
}

function restoreNodeColor() {
  if (!network) return;
  for (let [key, value] of Object.entries(optionColorRecovery)) {
    if (network.body.nodes[key]) {
      network.body.nodes[key].options.color.background = value;
    }
  }
  optionColorRecovery = {};
  network.redraw();
}

function neighbourhoodHighlight(core) {
  if (!network) return;
  restoreNodeColor();
  var connectedNodes = network.getConnectedNodes(core);
  connectedNodes.push(core);

  var bodyNodes = network.body.nodes;
  for (let [key, val] of Object.entries(bodyNodes)) {
    var numericKey = parseInt(key, 10);
    if (!connectedNodes.includes(numericKey)) {
      if (bodyNodes[key] && bodyNodes[key].options && bodyNodes[key].options.color) {
        optionColorRecovery[key] = bodyNodes[key].options.color.background;
        bodyNodes[key].options.color.background = 'rgba(226, 232, 240, 0.35)';
      }
    }
  }
  network.redraw();
}

function closeNodeInspector() {
  var inspector = document.getElementById('node-inspector');
  if (inspector) {
    inspector.classList.add('translate-x-[110%]', 'opacity-0', 'pointer-events-none');
    inspector.classList.remove('translate-x-0', 'opacity-100', 'pointer-events-auto');
  }
  var slideOver = document.getElementById('slideover-container');
  if (slideOver) {
    slideOver.classList.add('invisible');
  }
}

function toggleSlide(show) {
  if (show) {
    var inspector = document.getElementById('node-inspector');
    if (inspector) {
      inspector.classList.remove('translate-x-[110%]', 'opacity-0', 'pointer-events-none');
      inspector.classList.add('translate-x-0', 'opacity-100', 'pointer-events-auto');
    }
  } else {
    closeNodeInspector();
  }
}

function clickToExpand(event) {
  var source = (event && (event.target || event.srcElement)) || window.event?.target;
  if (!source) return;
  if (source.dataset.toggleto === '0') {
    source.dataset.toggleto = '1';
    source.textContent = source.dataset.long;
  } else {
    source.dataset.toggleto = '0';
    source.textContent = source.dataset.short;
  }
}

function selectAndFocusNode(id) {
  if (!network) return;
  network.selectNodes([id]);
  neighbourhoodHighlight(id);
  var targetNode = network.body.data.nodes.get(id);
  showNodeMetadata(targetNode, id);
  network.focus(id, {
    scale: 1.15,
    animation: {
      duration: 500,
      easingFunction: 'easeInOutQuad'
    }
  });
}

function showNodeMetadata(metadataOrNode, nodeId) {
  var inspector = document.getElementById('node-inspector');
  if (!inspector) return;

  var node = null;
  var metadata = {};
  if (metadataOrNode && metadataOrNode.nodetype !== undefined) {
    node = metadataOrNode;
    metadata = node.properties || {};
  } else if (network && network.body && network.body.data && network.body.data.nodes) {
    node = network.body.data.nodes.get(nodeId);
    metadata = (node && node.properties) ? node.properties : (metadataOrNode || {});
  } else {
    metadata = metadataOrNode || {};
  }

  // update inspector header details
  var color = node ? colorLookup(node.nodetype) : 'rgba(79, 70, 229, 0.75)';
  var colorDot = document.getElementById('inspector-color-dot');
  if (colorDot) {
    colorDot.style.backgroundColor = color;
  }
  var typeEl = document.getElementById('inspector-type');
  if (typeEl) {
    var rawType = node ? node.nodetype : 'Entity';
    typeEl.textContent = labelLookup(rawType);
  }
  var idEl = document.getElementById('inspector-id');
  if (idEl) {
    idEl.textContent = '#' + nodeId;
  }
  var titleEl = document.getElementById('inspector-title');
  if (titleEl) {
    titleEl.textContent = (node && node.label) ? node.label : ('Node #' + nodeId);
    titleEl.title = titleEl.textContent;
  }

  // update connection counters
  var connectedNodeIds = network ? network.getConnectedNodes(nodeId) : [];
  var localConnCount = connectedNodeIds.length;
  var countEl = document.getElementById('inspector-connections-count');
  if (countEl) {
    countEl.textContent = localConnCount;
  }
  var countConnectedHeading = document.getElementById('connected-count');
  if (countConnectedHeading) {
    countConnectedHeading.textContent = localConnCount;
  }

  // populate connected entities list
  var connectedList = document.getElementById('connected-nodes-list');
  if (connectedList) {
    connectedList.innerHTML = '';
    if (connectedNodeIds.length === 0) {
      connectedList.innerHTML = '<span class="text-xs text-slate-400">No connected nodes.</span>';
    } else {
      connectedNodeIds.forEach(function(neighborId) {
        var neighbor = network.body.data.nodes.get(neighborId);
        if (!neighbor) return;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors';
        var dot = document.createElement('span');
        dot.className = 'w-2 h-2 rounded-full flex-shrink-0';
        dot.style.backgroundColor = colorLookup(neighbor.nodetype);
        var lbl = document.createElement('span');
        lbl.className = 'truncate max-w-[140px]';
        lbl.textContent = neighbor.label || ('#' + neighbor.id);
        btn.appendChild(dot);
        btn.appendChild(lbl);
        btn.onclick = function() {
          selectAndFocusNode(neighbor.id);
        };
        connectedList.appendChild(btn);
      });
    }
  }

  // populate structured metadata attributes
  var metadataBox = document.getElementById('inspector-metadata-box');
  if (metadataBox) {
    metadataBox.innerHTML = '';
    var hasProps = false;
    for (var [label, value] of Object.entries(metadata)) {
      hasProps = true;
      var rawVal = Array.isArray(value) ? value[0] : value;
      if (rawVal === undefined || rawVal === null) rawVal = '';
      rawVal = String(rawVal);

      var propCard = document.createElement('div');
      propCard.className = 'p-2.5 bg-slate-50 rounded-xl border border-slate-200';

      var propLabel = document.createElement('div');
      propLabel.className = 'text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-0.5';
      propLabel.textContent = label;
      propCard.appendChild(propLabel);

      var propVal = document.createElement('div');
      propVal.className = 'text-xs text-slate-800 break-words';

      // check if wikidata identifier
      if (/^Q\d+$/.test(rawVal.trim())) {
        var wdLink = document.createElement('a');
        wdLink.href = 'https://www.wikidata.org/wiki/' + encodeURIComponent(rawVal.trim());
        wdLink.target = '_blank';
        wdLink.rel = 'noopener noreferrer';
        wdLink.className = 'inline-flex items-center gap-1 text-indigo-600 hover:underline font-mono';
        wdLink.innerHTML = '<span>' + escapeHtml(rawVal) + '</span><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
        propVal.appendChild(wdLink);
      } else if (/^https?:\/\//i.test(rawVal.trim())) {
        var urlLink = document.createElement('a');
        urlLink.href = rawVal.trim();
        urlLink.target = '_blank';
        urlLink.rel = 'noopener noreferrer';
        urlLink.className = 'inline-flex items-center gap-1 text-indigo-600 hover:underline break-all';
        urlLink.innerHTML = '<span>' + escapeHtml(rawVal) + '</span><svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
        propVal.appendChild(urlLink);
      } else if (rawVal.length > 160) {
        var shortVal = rawVal.substring(0, 160);
        var textSpan = document.createElement('span');
        textSpan.textContent = shortVal + '... ';
        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'text-indigo-600 hover:underline font-medium text-xs ml-1';
        toggleBtn.textContent = 'Show more';
        toggleBtn.setAttribute('data-state', 'collapsed');
        toggleBtn.onclick = (function(full, short, btn, span) {
          return function() {
            if (btn.getAttribute('data-state') === 'collapsed') {
              span.textContent = full + ' ';
              btn.textContent = 'Show less';
              btn.setAttribute('data-state', 'expanded');
            } else {
              span.textContent = short + '... ';
              btn.textContent = 'Show more';
              btn.setAttribute('data-state', 'collapsed');
            }
          };
        })(rawVal, shortVal, toggleBtn, textSpan);
        propVal.appendChild(textSpan);
        propVal.appendChild(toggleBtn);
      } else {
        propVal.textContent = rawVal;
      }

      propCard.appendChild(propVal);
      metadataBox.appendChild(propCard);
    }
    if (!hasProps) {
      metadataBox.innerHTML = '<span class="text-xs text-slate-400">No additional attributes recorded.</span>';
    }
  }

  // resolve persistent identifier and remote links
  var uriBox = document.getElementById('inspector-uri-box');
  var actionBox = document.getElementById('inspector-action-buttons');
  if (uriBox) {
    uriBox.innerHTML = '<span class="text-slate-400">Resolving persistent identifier...</span>';
  }
  if (actionBox) {
    actionBox.innerHTML = '';
  }

  var basePath = window.BASE_URL || '..';
  fetch(basePath + '/AJAX/identifyURI.php?id=' + encodeURIComponent(nodeId))
    .then(function(res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    })
    .then(function(data) {
      if (uriBox) {
        uriBox.innerHTML = '';
        if (data.uri && data.uri.length > 0) {
          var uri = String(data.uri[0]).trim();
          if (/^https?:\/\//i.test(uri)) {
            var uriLink = document.createElement('a');
            uriLink.href = uri;
            uriLink.target = '_blank';
            uriLink.rel = 'noopener noreferrer';
            uriLink.className = 'text-indigo-600 hover:underline truncate flex-1 font-mono text-[11px]';
            uriLink.title = uri;
            uriLink.textContent = uri;
            uriBox.appendChild(uriLink);
          } else {
            var uriSpan = document.createElement('span');
            uriSpan.className = 'text-slate-600 truncate flex-1 font-mono text-[11px]';
            uriSpan.title = uri;
            uriSpan.textContent = uri;
            uriBox.appendChild(uriSpan);
          }

          var copyBtn = document.createElement('button');
          copyBtn.type = 'button';
          copyBtn.className = 'p-1 text-slate-400 hover:text-slate-700 rounded hover:bg-slate-200 transition-colors';
          copyBtn.title = 'Copy URI to clipboard';
          copyBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
          copyBtn.onclick = function() {
            if (navigator.clipboard) {
              navigator.clipboard.writeText(uri).then(function() {
                copyBtn.innerHTML = '<svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                setTimeout(function() {
                  copyBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
                }, 2000);
              });
            }
          };

          uriBox.appendChild(copyBtn);
        } else {
          uriBox.innerHTML = '<span class="text-slate-400">No persistent URI available.</span>';
        }
      }

      if (data.connections !== undefined && countEl) {
        countEl.textContent = data.connections;
      }

      // add text reader action button if node represents a Text or has a Text ID
      if (actionBox && node) {
        var textId = null;
        var textPropKey = window.textPrimaryLabel || 'ID';
        if (metadata && metadata[textPropKey] && metadata[textPropKey].length > 0) {
          textId = metadata[textPropKey][0];
        } else if (metadata && metadata['Text ID'] && metadata['Text ID'].length > 0) {
          textId = metadata['Text ID'][0];
        } else if (metadata && metadata['ID'] && metadata['ID'].length > 0) {
          textId = metadata['ID'][0];
        } else if (node.nodetype === 'Text') {
          textId = nodeId;
        }
        if (textId) {
          var readBtn = document.createElement('a');
          readBtn.href = basePath + '/text.php?texid=' + encodeURIComponent(textId);
          readBtn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors';
          readBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span>Read full text</span>';
          actionBox.appendChild(readBtn);
        }
      }
    })
    .catch(function(err) {
      if (uriBox) {
        uriBox.innerHTML = '<span class="text-slate-400">Unable to load URI.</span>';
      }
    });

  // open inspector card with smooth slide animation
  inspector.classList.remove('translate-x-[110%]', 'opacity-0', 'pointer-events-none');
  inspector.classList.add('translate-x-0', 'opacity-100', 'pointer-events-auto');
}

function updateLegend() {
  var container = document.getElementById('legend-container');
  if (!container || !network) return;

  var nodes = network.body.data.nodes.get();
  var typeCounts = {};
  nodes.forEach(function(n) {
    var t = n.nodetype || 'Other';
    typeCounts[t] = (typeCounts[t] || 0) + 1;
  });

  container.innerHTML = '';
  Object.keys(typeCounts).sort().forEach(function(type) {
    var count = typeCounts[type];
    var isHidden = !!hiddenNodeTypes[type];
    var badge = document.createElement('button');
    badge.type = 'button';
    badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border transition-colors text-xs font-medium cursor-pointer ' +
      (isHidden
        ? 'opacity-50 line-through bg-slate-100 border-slate-200 text-slate-400'
        : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700');
    badge.title = (isHidden ? 'Show ' : 'Hide ') + labelLookup(type) + ' nodes';

    var dot = document.createElement('span');
    dot.className = 'w-2 h-2 rounded-full flex-shrink-0';
    dot.style.backgroundColor = isHidden ? '#94a3b8' : colorLookup(type);

    var text = document.createElement('span');
    text.textContent = labelLookup(type) + ' (' + count + ')';

    badge.appendChild(dot);
    badge.appendChild(text);

    badge.onclick = function() {
      toggleNodeTypeFilter(type);
    };

    container.appendChild(badge);
  });
}

function toggleNodeTypeFilter(type) {
  if (!network) return;
  hiddenNodeTypes[type] = !hiddenNodeTypes[type];
  var isHidden = hiddenNodeTypes[type];

  var allBodyNodes = network.body.data.nodes.get();
  var updates = [];
  allBodyNodes.forEach(function(n) {
    if (n.nodetype === type) {
      updates.push({ id: n.id, hidden: isHidden });
    }
  });

  if (updates.length > 0) {
    network.body.data.nodes.update(updates);
  }

  updateCounters();
  updateLegend();
}

function updateCounters() {
  var nodeDisplay = document.getElementById('nodeDisplay');
  var edgeDisplay = document.getElementById('edgeDisplay');
  if (network && network.body && network.body.data) {
    var allNodesList = network.body.data.nodes.get();
    var visibleNodes = allNodesList.filter(function(n) { return !n.hidden; });
    if (nodeDisplay) nodeDisplay.innerText = visibleNodes.length;
    if (edgeDisplay) edgeDisplay.innerText = allEdges.length;
  } else {
    if (nodeDisplay) nodeDisplay.innerText = allNodes.length;
    if (edgeDisplay) edgeDisplay.innerText = allEdges.length;
  }
}

function buildTopController() {
  // binds toolbar, inspector controls, and keyboard handlers
  var btnZoomIn = document.getElementById('btn-zoom-in');
  if (btnZoomIn) btnZoomIn.onclick = function() { zoom('in'); };

  var btnZoomOut = document.getElementById('btn-zoom-out');
  if (btnZoomOut) btnZoomOut.onclick = function() { zoom('out'); };

  var btnZoomFit = document.getElementById('btn-zoom-fit');
  if (btnZoomFit) btnZoomFit.onclick = function() { zoom('fit'); };

  var btnExtend = document.getElementById('btn-extend-graph');
  if (btnExtend) btnExtend.onclick = function() { extendGraphOneHop(); };

  var solverSelect = document.getElementById('physics-solver-select');
  if (solverSelect) {
    solverSelect.onchange = function() {
      toggleLayoutAlgo(this.value);
    };
  }

  var btnPhysics = document.getElementById('btn-physics-toggle');
  if (btnPhysics) btnPhysics.onclick = function() { togglePhysics(); };

  var btnFullscreen = document.getElementById('btn-fullscreen-toggle');
  if (btnFullscreen) btnFullscreen.onclick = function() { toggleFullscreen(); };

  var btnCloseInspector = document.getElementById('btn-close-inspector');
  if (btnCloseInspector) btnCloseInspector.onclick = function() { closeNodeInspector(); };

  var toggleConn = document.getElementById('toggle-connected-nodes');
  var connList = document.getElementById('connected-nodes-list');
  var connArrow = document.getElementById('connected-arrow');
  if (toggleConn && connList) {
    toggleConn.onclick = function() {
      connList.classList.toggle('hidden');
      if (connArrow) connArrow.classList.toggle('rotate-180');
    };
  }

  var toggleMeta = document.getElementById('toggle-metadata');
  var metaBox = document.getElementById('inspector-metadata-box');
  var metaArrow = document.getElementById('metadata-arrow');
  if (toggleMeta && metaBox) {
    toggleMeta.onclick = function() {
      metaBox.classList.toggle('hidden');
      if (metaArrow) metaArrow.classList.toggle('rotate-180');
    };
  }

  var backBtn = document.getElementById('btn-back');
  if (backBtn) {
    backBtn.onclick = function(e) {
      if (window.history.length > 1) {
        e.preventDefault();
        window.history.back();
      }
    };
  }

  // global keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
      return;
    }
    if (e.key === 'Escape') {
      closeNodeInspector();
      restoreNodeColor();
    } else if (e.key === '+' || e.key === '=') {
      zoom('in');
    } else if (e.key === '-' || e.key === '_') {
      zoom('out');
    } else if (e.key === '0' || e.key === 'f' || e.key === 'F') {
      zoom('fit');
    } else if (e.key === ' ') {
      e.preventDefault();
      togglePhysics();
    }
  });
}

function extendGraphOneHop() {
  if (!network) return;
  var btn = document.getElementById('btn-extend-graph');
  var textEl = document.getElementById('extend-text');
  var iconEl = document.getElementById('extend-icon');

  if (btn) btn.disabled = true;
  if (textEl) textEl.textContent = 'Extending...';
  if (iconEl) iconEl.classList.add('animate-spin');

  network.setOptions({ physics: false });
  var extendReplies = [];
  for (var x = 0; x < allNodes.length; x++) {
    if (!excludeNodeIdFromNextXHR.includes(allNodes[x])) {
      extendReplies.push(requestExtend(allNodes[x], false));
    }
  }

  Promise.all(extendReplies)
    .then(function(results) {
      results.forEach(function(result) {
        var dedupData = result[0];
        if (dedupData && dedupData[0] && dedupData[0].length !== 0) {
          network.body.data.nodes.update(dedupData[0]);
        }
        if (dedupData && dedupData[1] && dedupData[1].length !== 0) {
          network.body.data.edges.update(dedupData[1]);
        }
      });
      updateCounters();
      updateLegend();
    })
    .catch(function(error) {
      console.error('An error occurred during graph extension:', error);
    })
    .finally(function() {
      network.setOptions({ physics: isPhysicsEnabled });
      if (btn) btn.disabled = false;
      if (textEl) textEl.textContent = 'Extend 1-hop';
      if (iconEl) iconEl.classList.remove('animate-spin');
    });
}

function extend(id) {
  return new Promise(function(resolve, reject) {
    excludeNodeIdFromNextXHR.push(id);
    var basePath = window.BASE_URL || '..';
    var url = basePath + '/AJAX/browseDataProvider.php?value=' + encodeURIComponent(id);
    fetch(url)
      .then(function(response) {
        if (!response.ok) {
          throw new Error('HTTP error ' + response.status);
        }
        return response.json();
      })
      .then(function(data) {
        resolve(data);
      })
      .catch(function(error) {
        console.error('Failed to extend node data:', error);
        reject(error);
      });
  });
}

function preProcess(newNodes, newEdges) {
  var addTheseNodes = [];
  var addTheseEdges = [];
  for (let n = 0; n < newNodes.length; n++) {
    var node = newNodes[n];
    node.shape = 'dot';
    node.color = colorLookup(node.nodetype);
    if (allNodes.indexOf(node['id']) === -1) {
      addTheseNodes.push(node);
      allNodes.push(node['id']);
    }
  }
  for (var m = 0; m < newEdges.length; m++) {
    var edge = newEdges[m];
    var edgeId = edge['from'] + '_' + edge['to'];
    if (allEdges.indexOf(edgeId) === -1) {
      addTheseEdges.push(edge);
      allEdges.push(edgeId);
    }
  }
  updateCounters();
  return [addTheseNodes, addTheseEdges];
}

function requestExtend(onId, autocommit) {
  if (typeof autocommit === 'undefined') autocommit = true;
  return new Promise(function(resolve, reject) {
    extend(onId).then(function(data) {
      var dedupData = preProcess(data['nodes'] || [], data['edges'] || []);
      if (dedupData[0].length !== 0 && autocommit) {
        network.body.data.nodes.update(dedupData[0]);
      }
      if (dedupData[1].length !== 0 && autocommit) {
        network.body.data.edges.update(dedupData[1]);
      }
      updateLegend();
      if (!autocommit) {
        resolve([dedupData]);
      } else {
        resolve(dedupData);
      }
    }).catch(function(error) {
      reject(error);
    });
  });
}

function init(neoIDFromEgo) {
  buildTopController();
  extend(neoIDFromEgo).then(function(data) {
    var container = document.getElementById('viz');
    preProcess(data['nodes'] || [], data['edges'] || []);

    var defaultNetworkOptions = {
      interaction: {
        hover: true,
        hoverConnectedEdges: true,
        selectConnectedEdges: false,
        tooltipDelay: 200,
        navigationButtons: false,
        keyboard: false
      },
      nodes: {
        shape: 'dot',
        size: 16,
        font: {
          color: '#334155',
          size: 13,
          face: 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
          strokeWidth: 3,
          strokeColor: '#ffffff'
        },
        borderWidth: 2,
        borderWidthSelected: 3.5,
        shadow: {
          enabled: true,
          color: 'rgba(0, 0, 0, 0.08)',
          size: 6,
          x: 0,
          y: 3
        }
      },
      edges: {
        color: {
          color: '#cbd5e1',
          highlight: '#4f46e5',
          hover: '#94a3b8',
          inherit: false
        },
        width: 1.5,
        smooth: {
          type: 'continuous',
          roundness: 0.5
        },
        arrows: {
          to: {
            enabled: true,
            scaleFactor: 0.6
          }
        }
      },
      physics: {
        enabled: true,
        solver: 'barnesHut',
        barnesHut: {
          gravitationalConstant: -2800,
          centralGravity: 0.25,
          springLength: 95,
          springConstant: 0.04,
          damping: 0.09
        },
        stabilization: {
          iterations: 120,
          updateInterval: 25
        }
      }
    };

    network = new vis.Network(container, data, defaultNetworkOptions);
    updateLegend();

    // Populate the inspector for the initial seed node when present in the graph.
    if (neoIDFromEgo && network.body && network.body.data && network.body.data.nodes) {
      var seedNode = network.body.data.nodes.get(neoIDFromEgo);
      if (seedNode) {
        network.selectNodes([neoIDFromEgo]);
        neighbourhoodHighlight(neoIDFromEgo);
        showNodeMetadata(seedNode, neoIDFromEgo);
      }
    }

    // double-click to expand neighborhood
    network.on('doubleClick', function(properties) {
      if (!properties.nodes.length) return;
      var doubleClickedOn = properties.nodes[0];
      requestExtend(doubleClickedOn, true);
    });

    // single click to highlight neighborhood and open inspector
    network.on('click', function(properties) {
      if (!properties.nodes.length) {
        restoreNodeColor();
        closeNodeInspector();
        return;
      }
      var clickedOn = properties.nodes[0];
      var node = network.body.data.nodes.get(clickedOn);
      if (node) {
        neighbourhoodHighlight(clickedOn);
        showNodeMetadata(node, clickedOn);
      }
    });

    // right click to inspect node
    network.on('oncontext', function(params) {
      if (params.event) {
        params.event.preventDefault();
      }
      var nodePos = network.getNodeAt({ x: params.pointer.DOM.x, y: params.pointer.DOM.y });
      if (nodePos !== undefined) {
        var node = network.body.data.nodes.get(nodePos);
        if (node) {
          neighbourhoodHighlight(nodePos);
          showNodeMetadata(node, nodePos);
        }
      }
    });
  }).catch(function(err) {
    console.error('Failed to initialize graph explorer:', err);
  });
}
