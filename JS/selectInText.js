function saveSuggestion(){
  console.log('saved');
}

function ignoreSuggestion(){
  var isOpen = document.getElementById("suggestionOnSelect");
  //console.log(rangy.getWindow([1]));
  if (isOpen){
    isOpen.remove();
  }
}

function makeSuggestionBox(){
  ignoreSuggestion();
  var topDst = rangy.getSelection().anchorNode.parentElement.offsetTop;
  var height = rangy.getSelection().anchorNode.parentElement.offsetHeight;
  var leftDst = rangy.getSelection().anchorNode.parentElement.offsetLeft-125;
  if (leftDst < 10){
    leftDst = 10;
  }
  //create spinner;
  var spinner = document.createElement('div');
  spinner.innerHTML = '<div id="suggestionboxspinner" class="text-center"> <svg role="status" class="inline w-8 h-8 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">         <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895  90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>  <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>  </svg> </div>';
  //create div at fixed position:
  var div = document.createElement('div');
  var tex = document.createTextNode('Entities: ');
  var texheader = document.createElement('H3');
  texheader.appendChild(tex);
  texheader.classList.add('bg-teal-300','flex', 'justify-content');
  div.appendChild(texheader);
  div.appendChild(spinner);
  div.classList.add('suggestionBox', 'bg-white');
  div.style.position =  'absolute';
  div.style.top = topDst+height+'px';
  div.style.left = leftDst+'px';
  div.style.minWidth = '250px';
  div.style.maxWidth = '300px';
  div.style.minHeight = '100px';
  div.style.maxHeight = '200px';
  div.setAttribute('id', 'suggestionOnSelect');
  //save/dismiss button:
  var buttonsBottom = document.createElement('div');
  var save = document.createElement('button');
  save.addEventListener('click', function(){saveSuggestion();});
  var savetext = document.createTextNode('Save');
  var dismiss = document.createElement('button');
  dismiss.addEventListener('click', function(){console.log('detect');ignoreSuggestion();});
  var dismisstext = document.createTextNode('Dismiss');
  buttonsBottom.classList.add('w-full', 'mt-auto', 'p-2');
  save.disabled = true;
  save.classList.add('bg-green-400', 'w-1/2', 'disabled:opacity-25', 'disabled:cursor-not-allowed');
  save.setAttribute('id', 'suggestionbox_saveButton');
  dismiss.classList.add('bg-red-400', 'w-1/2');
  save.appendChild(savetext);
  dismiss.appendChild(dismisstext);
  dismiss.setAttribute('id', 'suggestionbox_dismissButton');
  buttonsBottom.appendChild(save);
  buttonsBottom.appendChild(dismiss);
  div.appendChild(buttonsBottom);
  document.body.appendChild(div);
}

function loadIntoSuggestionBox(data, from, to){
  console.log(data);
  document.getElementById('suggestionbox_saveButton').disabled = false;
  var datadiv = document.createElement('div');
  var metadataOnSearch = document.createElement('div');
  metadataOnSearch.classList.add('suggestionMetadata');
  var dataOnSearch = document.createElement('div');
  dataOnSearch.classList.add('suggestionData');
  var edgesInfo = document.createElement('p');
  var keySpanEdge = document.createElement('span');
  var edgesKey = document.createTextNode('Edges: ')
  keySpanEdge.appendChild(edgesKey);
  var nodesInfo = document.createElement('p');
  var keySpanNode = document.createElement('span');
  var nodesKey = document.createTextNode('Nodes: ')
  keySpanNode.appendChild(nodesKey);
  keySpanNode.classList.add('font-bold');
  keySpanEdge.classList.add('font-bold');
  var coreNodes = Object.keys(nodeDefinitions);
  var retrievedCoreElements = data.nodes.filter(node => coreNodes.includes(node[1]));
  var valueSpanEdge = document.createTextNode(data.edges.length);
  var valueSpanNode = document.createTextNode(data.nodes.length+' | '+retrievedCoreElements.length);
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
  document.getElementById("suggestionboxspinner").parentNode.insertBefore(datadiv, document.getElementById('suggestionboxspinner'));
  document.getElementById('suggestionboxspinner').remove();
}

function scanForOtherOccurences(normalization){
  if(normalization){

  }
}

function getTextSelection(){
    var text = rangy.getSelection().toString().trim();
    //you need a map filter on selection based on length of childnodes!
    var selection = rangy.getSelection().getRangeAt(0).getNodes().filter(s => s.childNodes.length == 0);
    //get first and last selection elements to extract data attribute:
    var startOfEntitySelection = parseInt(selection[0].parentElement.dataset.itercounter);
    var endOfEntitySelection = parseInt(selection[selection.length-1].parentElement.dataset.itercounter);
    return [text, startOfEntitySelection, endOfEntitySelection];
}

function triggerSelection(){
    var selectedTextProperties = getTextSelection();
    var selectedText = selectedTextProperties[0];
    var selectedTextStart = selectedTextProperties[1];
    var selectedTextEnd = selectedTextProperties[2];
    //fetch from BE:
    //$findEntityByType = $_GET['type'];
    //$findEntityByValue = $_GET['value'];
    if(selectedText){
      $baseURL = '/AJAX/getEntitySuggestion.php?';
      $parameters = {
        'type':'',    //type is empty as there was no pickup by NERtool
        'value':selectedText,
        'casesensitive':false
      };
      $sendTo = $baseURL+jQuery.param($parameters);
      makeSuggestionBox();
      getInfoFromBackend($sendTo)
      .then((data)=>{
        loadIntoSuggestionBox(data, selectedTextStart, selectedTextEnd);
      })
    }
}

$(document).ready(function() {
  var triggerpoints = document.getElementsByClassName('ltr');
  for(var i = 0; i < triggerpoints.length; i++){
    triggerpoints[i].addEventListener('mouseup', function(){triggerSelection()});
    triggerpoints[i].addEventListener('keyup', function(){triggerSelection()});
  }
  //use esc key to delete the suggestionbox:
  document.addEventListener('keyup', function(event) {
   if (event.keyCode === 27) {
     ignoreSuggestion();
   }
 });
});
