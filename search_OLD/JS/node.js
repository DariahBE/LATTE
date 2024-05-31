
function getTickedLabel(){
  var nodeLabel = false;
  var labels = document.getElementById('nodeTypes').getElementsByClassName('subbox')[0].getElementsByTagName('input');
  for (var i = 0; i < labels.length; i++){
    var label = labels[i];
    if(label.checked){
      nodeLabel = label.value;
      //console.log(label.value);
      continue;
    }
  }
  return nodeLabel; 
} 



function performBasicSearch(){
  //get the nodeLabel:
  var nodeLabel = getTickedLabel(); 
  //get the filled out properties:
  var properties = document.getElementById('nodeProperties').getElementsByClassName('subbox')[0].getElementsByTagName('input');
  //console.log(properties);
  var propdict = {};
  for (var i = 0; i < properties.length; i++){
    var prop = properties[i];
    if (prop.value){
      propdict[prop.name] = prop.value;
    }
  }
  searchDict = {
    'nodes':{
      'node_1':{
        'label':nodeLabel,
        'property':propdict
      }
    },
    'edges':{}
  }
  sessionStorage.setItem("mySearchCommand", JSON.stringify(searchDict));
  window.location = 'results.php';
}

function labelSelected(){
  // console.log('selected');
  var source = event.source||event.target;
  var target = document.getElementById('nodeProperties').getElementsByClassName('subbox')[0];
  fetch('AJAX/nodes.php?action=properties&on='+source.value)
    .then(response => response.json())
    .then(data => {
      // console.log(data);
      var form = document.createElement('form');
      form.classList.add('w-full');
      var maindiv = document.createElement('div');
      maindiv.classList.add('items-center', 'mb-6', 'w-full');
      for(var i = 0; i < data.length; i++){
        var keyData = data[i];
        var keyDB = keyData[0];
        var keyHR = keyData[1];
        if (!(keyHR)){
          keyHR = keyDB;
        }
        var grp = document.createElement('div');
        grp.classList.add('input-group', 'p-2', 'm-2', 'w-full', 'items-center', 'border-2', 'align-middle');
        var input = document.createElement('input');
        input.setAttribute('name', keyDB);
        input.classList.add('border-2', 'rounded', 'md:w-2/3', 'float-right' );
        var label = document.createElement('label');
        label.setAttribute('for', keyDB);
        label.classList.add('md:w-1/3', 'float-left');
        var labelTex = document.createTextNode(keyHR);
        label.appendChild(labelTex);
        grp.appendChild(label);
        grp.appendChild(input);
        maindiv.appendChild(grp);
      }
      form.appendChild(maindiv);
      target.innerHTML = '';
      target.appendChild(form);
      var searchButton = document.createElement('button');
      var searchButtonTex = document.createTextNode('Search');
      searchButton.appendChild(searchButtonTex);
      searchButton.classList.add('shrink');
      searchButton.addEventListener('click', function(){
        performBasicSearch();
      });
      target.appendChild(searchButton);

    });
  addEdgeFilter();
}

function addEdgeFilter(){
  var target = document.getElementById("edgeFilter"); 
  target.classList.remove('hidden');
  var contentTarget = document.getElementById('edgeFilterInnercontent');
  contentTarget.innerHTML = ''; 
  fetch('AJAX/nodes.php?action=connections&on='+getTickedLabel())
    .then(response => response.json())
    .then(data => {
      // console.log(data);
      for(var i = 0; i < data.length; i++){
        var oneRelation = data[i];
        var relationDB = oneRelation[0];
        var relationHuman = oneRelation[1];
        var grp = document.createElement('div');
        grp.classList.add('form-check', 'border-2');
        var input = document.createElement('checkbox');
        input.classList.add('form-check-input', 'appearance-none', 'rounded-full', 'h-4', 'w-4', 'border', 'border-gray-300', 'bg-white', 'checked:bg-blue-600', 'checked:border-blue-600', 'focus:outline-none', 'transition', 'duration-200', 'mt-1', 'align-top', 'bg-no-repeat', 'bg-center', 'bg-contain', 'float-left', 'mr-2', 'cursor-pointer');
        input.setAttribute('name', 'edgeLabel');
        input.setAttribute('value', relationDB); 
        var label = document.createElement('label');
        label.setAttribute('for', relationDB);
        label.classList.add('md:w-1/3', 'float-left');
        var labelTex = document.createTextNode(relationHuman ? relationHuman : relationDB);
        label.appendChild(labelTex);
        grp.appendChild(label);
        grp.appendChild(input);
        contentTarget.appendChild(grp);
      }
    });
}


function addSecondNode(){
  // console.log('TODO: add a target node to the original selection!'); 
  var target = document.getElementById('secondnodeFilter');
  target.classList.remove('hidden');
  var innertarget = document.getElementById('nodeFilterInnercontent'); 
  innertarget.innerHTML = ''; 
  fetch('AJAX/nodes.php?action=targetnode&on='+getTickedLabel()+'&over=')
    .then(response => response.json())
    .then(data => {
      for (var i = 0; i < data.length; i++){
        var datarow = data[i]; 
      }
    })
}

function searchInit() {
  var primaryDivTarget = document.getElementById('nodeTypes').getElementsByClassName('subbox')[0];
  fetch('AJAX/nodes.php?action=labels')
    .then(response => response.json())
    .then(data => {
      for (var i = 0; i < data.length; i++){
        var labelData = data[i];
        var labelDB = labelData[0];
        var labelHR = labelData[1];
        if (!(labelHR)){
          labelHR = labelDB;
        }
        var input = document.createElement('input');
        input.setAttribute('type', 'radio');
        input.setAttribute('name', 'nodeLabel');
        input.setAttribute('value', labelDB);
        input.classList.add('form-check-input', 'appearance-none', 'rounded-full', 'h-4', 'w-4', 'border', 'border-gray-300', 'bg-white', 'checked:bg-blue-600', 'checked:border-blue-600', 'focus:outline-none', 'transition', 'duration-200', 'mt-1', 'align-top', 'bg-no-repeat', 'bg-center', 'bg-contain', 'float-left', 'mr-2', 'cursor-pointer');
        var label = document.createElement('p');
        var labelTex = document.createTextNode(labelHR);
        label.appendChild(labelTex);
        var labelGroup = document.createElement('div');
        labelGroup.classList.add('form-check', 'border-2');
        labelGroup.appendChild(input);
        labelGroup.appendChild(label);
        input.addEventListener('click', function(){
          labelSelected();
          }
        );
        primaryDivTarget.appendChild(labelGroup);
      }
    });
}
