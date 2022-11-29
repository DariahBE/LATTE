var allNodes = [];
var allEdges = []; 
var network = null;


function colorLookup(label){
  //return 'rgba(123,123,123,0.8';
  return colorDefinitions[label];
}


function extend(id){
  return new Promise((resolve, reject) => {
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
    node.shape = 'circle';
    node.color = colorLookup(node.label);
    if(allNodes.indexOf(node['id']) === -1){
      addTheseNodes.push(node);
      allNodes.push(node['id']);
    }
  }
  //preprocess Edges: 
  for (let n = 0; n < newEdges.length; n++) {
    const edge = newEdges[n];
    const edgeId = edge['from']+'_'+edge['to'];
    if(allEdges.indexOf(edgeId) === -1){
      addTheseEdges.push(edge);
      allEdges.push(edgeId);
    }
  }
  return [addTheseNodes, addTheseEdges];
}

function init(neoIDFromEgo){
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
        extend(doubleClickedOn).then((data) => {
          var dedupData = preProcess(data['nodes'], data['edges']);
          network.body.data.nodes.update(dedupData[0]);
          network.body.data.edges.update(dedupData[1]);
        })
    });
  }
  )
}