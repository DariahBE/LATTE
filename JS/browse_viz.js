var allNodes = [];
var allEdges = []; 
var network = null;
var optionColorRecovery = {};
var excludeNodeIdFromNextXHR = [];
var c = 0; 

function zoom(to){
  switch(to){
    case "in":
      network.view.body.view.scale = network.view.body.view.scale / 0.9;
      network.redraw();
      break;
    case "out": 
      network.view.body.view.scale = network.view.body.view.scale*0.9
      network.redraw();
      break;
    default:
      network.fit();
      network.redraw();
      break;
  }
}

function toggleLayoutAlgo(model){
  network.setOptions({physics:{solver:model}}) 
}

function restoreNodeColor(){
  for(let [key, value] of Object.entries(optionColorRecovery)){
    network.body.nodes[key].options.color.background = optionColorRecovery[key];
  } 
  network.redraw(); 
}


function neighbourhoodHighlight(core){
  restoreNodeColor();
  var connectedNodes = network.getConnectedNodes(core);
  var connectedEdges = network.getConnectedEdges(core);
  connectedNodes.push(core);
  // for every node not in connectedNodes: take away the color: 
  var allNodes = network.body.nodes;
  for(let [key, value] of Object.entries(allNodes)){
    if (!(connectedNodes.includes(parseInt(key)))){
      optionColorRecovery[key] = network.body.nodes[key].options.color.background;
      network.body.nodes[key].options.color.background='rgba(225,225,225,0.6)';
    }
  }
  network.redraw();
}

function toggleSlide(show){
  if(show){
    document.getElementById('slideover-container').classList.remove('invisible');
    document.getElementById('slideover').classList.remove('translate-x-full');
  }else{  //if false or no argument is passed ==> hide it
    document.getElementById('slideover-container').classList.add('invisible');
    document.getElementById('slideover').classList.add('translate-x-full');
  }
}

/* when a node has a property that's too long for the dom; it's shrunk. Clicking it 
toggles the full text to show/hid */
function clickToExpand(){
  var source = event.target || event.srcElement;
  if (source.dataset.toggleto == 0){
    source.dataset.toggleto = 1; 
    source.textContent = source.dataset.long;
  }else{
    source.dataset.toggleto = 0;
    source.textContent = source.dataset.short;
  }
}

function showNodeMetadata(metadata, nodeId){
  //uncollapse the side-slideOver panel: 
  toggleSlide(true);
  //generate links to see the node, use the URI components for that.
  //underneath each generated link; show the node properties:
  const metadataBox = document.createElement('div'); 
  for (var[label,value] of Object.entries(metadata)){
    value = value[0];
    var metadataDisplay = document.createElement('p');
    var metadataLabel = document.createElement('span');
    metadataLabel.classList.add('font-bold');
    var metadataValue = document.createElement('span'); 
    metadataValue.setAttribute('data-original', value);
    if (value.length > 256){
      var shortValue = value.substring(0,256)+' ((see more...))'; 
      metadataValue.setAttribute('data-short', shortValue);
      metadataValue.setAttribute('data-long', value); 
      metadataValue.setAttribute('data-toggleto', 0);
      metadataValue.addEventListener('click', function(){clickToExpand();});
      metadataValue.appendChild(document.createTextNode(shortValue));
    }else{
      metadataValue.appendChild(document.createTextNode(value)); 
    }
    metadataLabel.appendChild(document.createTextNode(label+': '));
    metadataDisplay.appendChild(metadataLabel);
    metadataDisplay.appendChild(metadataValue);
    metadataBox.appendChild(metadataDisplay); 
  }
  const metadataTarget = document.getElementById('metadataboxGoesHere');
  metadataTarget.innerHTML = ''; 
  metadataTarget.appendChild(metadataBox);
  // URI component: 
  $.ajax('/AJAX/identifyURI.php?id='+nodeId)
  .done(function(data){
    const uriTarget = document.getElementById('nodeLinkBox'); 
    uriTarget.innerHTML= '';
    //console.log(data);
    if(data.uri.length === 1){
      var uriDisplay = document.createElement('a'); 
      uriDisplay.href = data[0]; 
      var uriText = document.createTextNode(data['uri'][0]); 
      uriDisplay.appendChild(uriText); 
      uriTarget.appendChild(uriDisplay);
      counttarget = document.getElementById('nodestatisticsBox');
      counttarget.innerHTML ='';
      let holdStat = document.createElement('span'); 
      holdStat.appendChild(document.createTextNode(data['connections']));
      counttarget.appendChild(holdStat);
    }else{
      uriTarget.innerHTML = 'N/A';
    }
  })
}

function buildTopController(){
  const target = document.getElementById('top');
  const topdiv = document.createElement('div');
  const topLeft = document.createElement('div');
  const topMid = document.createElement('div');
  const topRight = document.createElement('div'); 
  topdiv.classList.add("bg-red-100", "w-full", "h-24", 'grow', 'flex-row');
  topLeft.classList.add("w-1/4", "bg-yellow-100", "inline-block", "h-22");
  topRight.classList.add("w-1/2", "bg-green-100", "inline-block", "h-22");
  topMid.classList.add("w-1/4",  "bg-blue-100", "inline-block", "h-22");
  //create statistics in topLeft div: 
  const countNodes = document.createElement('p');
  const countNodesSubElement = document.createElement('span');
  countNodesSubElement.setAttribute('id', 'nodeDisplay');
  const countNodesLabel = document.createTextNode('Nodes: ');
  countNodes.appendChild(countNodesLabel);
  countNodes.appendChild(countNodesSubElement);
  const countEdges = document.createElement('p');
  const countEdgesSubElement = document.createElement('span');
  countEdgesSubElement.setAttribute('id', 'edgeDisplay');
  const countEdgesLabel = document.createTextNode('Edges: ');
  countEdges.appendChild(countEdgesLabel);
  countEdges.appendChild(countEdgesSubElement); 
  topLeft.appendChild(countNodes);
  topLeft.appendChild(countEdges); 
  //in topLeft div: add one button that extends the graph one hop to all sides.
  const bt = document.createElement('button'); 
  bt.classList.add('btn', 'bg-blue-400');
  const btText = document.createTextNode('Extend graph'); 
  bt.appendChild(btText); 
  bt.onclick = function(){
    extendGraphOneHop();
  }
  topLeft.appendChild(bt);
  topdiv.appendChild(topLeft);
  //add and create a zoombox to middle:
  const midZoomBox = document.createElement('div'); 
  const zoomOut = document.createElement('button'); 
  const zoomOutLabel = document.createElement('svg');
  zoomOutLabel.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM13.5 10.5h-6" /></svg>';
  const zoomToFit = document.createElement('button'); 
  const zoomToFitLabel = document.createElement('svg'); 
  zoomToFitLabel.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /> </svg>'; 
  const zoomIn =  document.createElement('button'); 
  const zoomInLabel = document.createElement('svg');
  zoomInLabel.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" /> </svg>'; 
  zoomOut.appendChild(zoomOutLabel);
  zoomToFit.appendChild(zoomToFitLabel);
  zoomIn.appendChild(zoomInLabel);
  zoomOut.onclick = function(){
    zoom('out');
  }
  zoomToFit.onclick = function(){
    zoom();
  }
  zoomIn.onclick = function(){
    zoom('in');
  }
  midZoomBox.appendChild(zoomIn);
  midZoomBox.appendChild(zoomToFit);
  midZoomBox.appendChild(zoomOut);
  //add a dropdown to modify the layout algorithm!
  const midModelBox = document.createElement('div');
  const midModelSelect = document.createElement('select');
  midModelSelect.onchange =function(){
    toggleLayoutAlgo(this.value);
  };
  const models = [
    ["Barnes Hut", "barnesHut"],
    ["Repulsion", "repulsion"],
    ["Hierarchical Repulsion", "hierarchicalRepulsion"],
    ["ForceAtlas 2", "forceAtlas2Based"]
  ];
  for (let model = 0; model < models.length; model++) {
    const optionValues = models[model];
    const option = document.createElement('option'); 
    option.appendChild(document.createTextNode(optionValues[0]));
    option.setAttribute('value', optionValues[1]); 
    midModelSelect.appendChild(option);
  }
  midModelBox.appendChild(midModelSelect);

  //add to the middle layout the zoombox, layoutbox,
  topMid.appendChild(midZoomBox);
  topMid.appendChild(midModelBox);
  topdiv.appendChild(topMid);
  topdiv.appendChild(topRight);
  target.appendChild(topdiv);
}

function colorLookup(label){
  return colorDefinitions[label];
}

function extendGraphOneHop(){
  //stop rendering the network while the graph is being extended
  network.setOptions( { physics: false } );
  for(var x = 0; x < allNodes.length; x++){
    if (!(excludeNodeIdFromNextXHR.includes(allNodes[x]))){
      requestExtend(allNodes[x]);
    }
  } 
  //once all nodes are in the graph: redraw.
  //BUG: network is being updated before all nodes are in there. Enforce it to wait for all pending AJAX-requests.
  network.setOptions( { physics: true } );
}


function extend(id){
  return new Promise((resolve, reject) => {
    //if the XHR-call has been made: exclude it from doing it again next time. There's not going to be anything new.
    excludeNodeIdFromNextXHR.push(id);
    $.ajax("../AJAX/browseDataProvider.php?value="+id)
    .done(function(data){
      resolve(data);
    })
  })
}
function preProcess(newNodes, newEdges){
  var addTheseNodes = [];
  var addTheseEdges = [];
  //preprocess Nodes: 
  for (let n = 0; n < newNodes.length; n++) {
    var node = newNodes[n];
    node.shape = 'dot';
    node.color = colorLookup(node.nodetype);
    if(allNodes.indexOf(node['id']) === -1){
      addTheseNodes.push(node);
      allNodes.push(node['id']);
    }
  }
  //preprocess Edges: 
  for (var m = 0; m < newEdges.length; m++) {
    var edge = newEdges[m];
    var edgeId = edge['from']+'_'+edge['to'];
    if(allEdges.indexOf(edgeId) === -1){
      addTheseEdges.push(edge);
      allEdges.push(edgeId);
    }
  }
  updateCounters();
  return [addTheseNodes, addTheseEdges];
}

function updateCounters(){
  document.getElementById('edgeDisplay').innerText = allEdges.length; 
  document.getElementById('nodeDisplay').innerText = allNodes.length; 
}

function requestExtend(onId){
  extend(onId).then((data) => {
    var dedupData = preProcess(data['nodes'], data['edges']);
    //if there's a new node; add it:
    if (dedupData[0].length !== 0){
      network.body.data.nodes.update(dedupData[0]);
    }
    // if there's a new edge; add it: 
    if(dedupData[1].length !== 0){
      network.body.data.edges.update(dedupData[1]);
    }
  })
}

function init(neoIDFromEgo){
  buildTopController();
  extend(neoIDFromEgo).then((data) => {
    var options = {};
    var container = document.getElementById('viz');
    //register the initial nodes and edges!
    preProcess(data['nodes'], data['edges']);
    network = new vis.Network(container, data, options);
    // double click to extend node: get all nodes and edges where the clicked node is part of!
    network.on("doubleClick", function(properties) {
      if(!properties.nodes.length){return};
      var doubleClickedOn = properties.nodes[0];
      requestExtend(doubleClickedOn);
    });
    network.on("click", function(properties){
      restoreNodeColor();
      if(!properties.nodes.length){return};
      var clickedOn = properties.nodes[0];
      var node = network.body.data.nodes.get(clickedOn); 
      if (null !== node){
        neighbourhoodHighlight(clickedOn);
      }
      toggleSlide(0);
    }); 
    network.on("oncontext", function(){
      event.preventDefault();
      var x = event.clientX; 
      var y = event.clientY; 
      var clickedOn = network.getNodeAt({'x': x, 'y': y});
      var node = network.body.data.nodes.get(clickedOn); 
      showNodeMetadata(node['properties'], clickedOn);
    });
    //body.on('click', function(){toggleSlide(0);})
  } 
  )
}


