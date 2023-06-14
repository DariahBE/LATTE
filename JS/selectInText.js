function saveSuggestion(){
  console.log('saved');
}

function ignoreSuggestion(){
  var isOpen = document.getElementById("suggestionOnSelect");
  if (isOpen){
    isOpen.remove();
  }
}


function toggleSlide(dir = 0){
  // 0 closes the sidepanel; 1 opens it. Better than the original .toggle() functions
  if(dir === 0){
    document.getElementById('slideover-container').classList.add('invisible');
    //document.getElementById('slideover-bg').classList.add('opacity-0');
    //document.getElementById('slideover-bg').classList.remove('opacity-50');
    document.getElementById('slideover').classList.add('translate-x-full');
  }else{
    document.getElementById('slideover-container').classList.remove('invisible');
    //document.getElementById('slideover-bg').classList.add('opacity-50');
    //document.getElementById('slideover-bg').classList.remove('opacity-0');
    document.getElementById('slideover').classList.remove('translate-x-full');
  }
}

function loadPropertiesOfSelectedType(selectedString){
  var selector = document.getElementById('entityTypeSelector'); 
  var selected = selector.value; //dropdown value selected. 
  //load properties for selected: 
  //start by testing if a variant exists for the given string: all string values are stored as variants; Variant should be connected to an entity of type == selected. 
  /*  BUG: uncontrolled response causes crash of app
  fetch('/AJAX/match_variant.php?value='+selectedString+'&connectedto='+selected)
  .then((response) => response.json())
  .then((data) =>{
    console.log(data);
  })*/
  //create a dual display: one with the option to add a new entity, one with the option to attach the annotation to an existing annotation. 
  fetch('/AJAX/get_structure.php?type='+selected)
  .then((response) => response.json())
  .then((data) =>{
    if(data['msg'] == 'success'){
      var nodedata = data['data']; 
      //console.log(nodedata); 
      Object.entries(nodedata).forEach(entry => {
        const [key, value] = entry;
        //console.log(key, value);
        var humanLabel = value[0];
        var datatype = value[1];
      });
      
    }
  })
}

function generateHyperlink(anchor, href, classlist=[], id=false, anchormode='text', newtab=true){
  var a = document.createElement('a'); 
  a.setAttribute('href', href);
  if(newtab){
    a.setAttribute('target','_blank');
  }
  for(let i = 0; i < classlist.length; i++){
    a.classList.add(classlist[i]); 
  }
  if(anchormode =='text'){
    var t = document.createTextNode(anchor); 
  }else if (anchormode =='image'){
    var t = document.createElement('img'); 
    t.setAttribute('src', anchor); 
  }
  a.appendChild(t); 
  if (id){
    a.setAttribute('id', id); 
  }
  return a; 
}

function showET(etdata){
  let wd = null; 
  let wdboxToDrop = document.getElementById('handyLittleThingyForWDStuff');
  if(wdboxToDrop){wdboxToDrop.remove();}
  const subtarget = document.getElementById('entitycontent');
  subtarget.innerHTML = ''; 
  console.log(etdata);
  var label = etdata[1];
  var properties = etdata[2];
  console.log('propers: '); 
  console.log(properties);
  var propdiv = document.createElement('div'); 
  for(let k in properties){
    let show = null; 
    let key = k;
    let value = properties[k]; 
    let valueType = value['vartype']; 
    let valueDOM = value['DOMString']; 
    let datavalue = value['value']; 
    if (valueType == 'uri'){
      show = generateHyperlink(valueDOM, datavalue, ['externalURILogo']); 
    }else if(valueType == 'wikidata' && datavalue !== null){
      console.log("wikidata box: ");
      console.log(value);
      show = document.createElement('p')
      var wdprefix = document.createElement('span'); 
      wdprefix.appendChild(document.createTextNode(valueDOM+': '));
      wdprefix.classList.add('font-bold'); 
      let extrashow = generateHyperlink(datavalue, 'https://wikidata.org/wiki/'+datavalue, ['externalURILogo']); 
      show.appendChild(wdprefix);
      show.appendChild(extrashow);
    }else{
      if(datavalue === null){datavalue='';}
      show = document.createElement('p'); 
      let labelShow = document.createElement('span'); 
      let valueShow = document.createElement('span'); 
      labelShow.classList.add('font-bold'); 
      let labelShowTex = document.createTextNode(valueDOM+': '); 
      let valueShowTex = document.createTextNode(datavalue); 
      labelShow.appendChild(labelShowTex);
      valueShow.appendChild(valueShowTex);
      show.appendChild(labelShow);
      show.appendChild(valueShow);
    }
    propdiv.appendChild(show); 
  }
  document.getElementById('entitycontent').appendChild(propdiv); 
  var wikidataID = etdata[3];
  if(wikidataID){
    wd = new wikibaseEntry(wikidataID, wdProperties, 'slideover', 'qid');
    wd.getWikidata()
      .then(function(){wd.renderEntities(wikidataID)}); 
  }
  //with the data displayed: allow the user to accept the suggestion => this creates a new annotation between
  //the text and existing ET. 
  console.log('accept/reject suggestion'); 
  var d=document.getElementById('assignEtToSelection');
  if(d!==null){d.remove();}
  fetch('/user/AJAX/profilestate.php')
  .then((response) => response.json())
  .then((data) =>{
    if(data['valid']){
      var csrf = data['csrf'];
      var acceptLink = document.createElement('button');
      acceptLink.setAttribute('id', 'assignEtToSelection')
      //console.log(selectedText, selectedTextStart,  selectedTextEnd); 
      var acceptText = document.createTextNode('Create annotation');
      acceptLink.appendChild(acceptText);
      acceptLink.classList.add('bg-green-400'); 
      acceptLink.addEventListener('click', function(){
        //make button unresponsive: 
        acceptLink.disabled = true;  
        //data to send to server

        let postData = {
          sourceNeoID: etdata[0],
          texNeoid: languageOptions['nodeid'], 
          csrf: csrf, 
          start: globalSelectionStart, 
          stop: globalSelectionEnd,
          selection: globalSelectionText
        }; 
        $.ajax({
          type: "POST",
          url: "/AJAX/crud/connect.php",
          data: postData,
          dataType: "json",
          success: function(repldata){
            //console.log(data); 
            //let repldata = JSON.parse(json);
            console.log(repldata); 
            let repl = document.createElement('p'); 
            repl.appendChild(document.createTextNode(repldata['msg'])); 
            document.getElementById('etmain').appendChild(repl); 
            let annotationStart = repldata['start'];
            let annotationEnd = repldata['stop']; 
            let annotationUID = repldata['annotation'];
            let annotationForType = repldata['type'];
          }
        }).always(
          function(){
            document.getElementById('assignEtToSelection').remove(); //delete annotation button
          }
        )
      }); 
      document.getElementById('etmain').appendChild(acceptLink); 
    }
  })

}

function triggerSidePanelAction(entityData){
  toggleSlide(1);
  //console.log(entityData);
  let = dataDictionary = {};
  const targetOfInfo = document.getElementById('slideoverDynamicContent'); 
  targetOfInfo.innerHTML = ''; 
  //backend returned one or more nodes that have  spellingvariant/label matching the request: 
  if(entityData['nodes'].length){
    //create a title that show the information about the matching entities: 
    let topbox = document.createElement('div'); 
    topbox.classList.add('w-full');
    let topTex = document.createElement('h3'); 
    topTex.classList.add('w-full'); 
    //create a box notice where the information is shown: 
    //find a way of attaching variants to the nodes!!
    //start with interpreting the edges: connect the entitynode with the variants once you know that!
    //BUG: entityID gets repeated on one to many relations with variants!
    dataDictionary = entityData['nodes'];

    topTex.appendChild(document.createTextNode("Found "+dataDictionary.length+" nodes based on matching string.")); 
    topbox.appendChild(topTex);
    targetOfInfo.appendChild(topbox);


    for (let k of Object.keys(dataDictionary)) {
      dataDictionary[k]['weight'] = entityData['weights'][dataDictionary[k][0]]; 
    }
    //sort the entities according to their score coming from the backend: 
    let sortedEntityKeys = []; 
    Object.keys(dataDictionary).sort(score);
    function score(a, b){
      return dataDictionary[a]['weight'] - dataDictionary[b]['weight'];
    }
    //node with the heighest weight is presented first: 
    //load the first node: 
    var firstNode = dataDictionary[0]; 
    var etMainBox = document.createElement('div'); 
    var etSubNavBox = document.createElement('div');
    var etSubContentBox = document.createElement('div'); 
    etMainBox.setAttribute('id', 'etmain'); 
    etSubNavBox.setAttribute('id', 'etnav'); 
    etSubContentBox.setAttribute('id', 'entitycontent'); 
    etMainBox.appendChild(etSubNavBox);
    etMainBox.appendChild(etSubContentBox);
    targetOfInfo.appendChild(etMainBox);
    showET(firstNode); 
    var datadictpage = 0;
    var pageLength = dataDictionary.length; 

    function navET(dir){
      if(dir === '-'){
        //go back
        datadictpage--;
        if (datadictpage <= 0){
          datadictpage = 0;
          document.getElementById('ETSuggestionArrowLeft').classList.add('invisible');
        }
      }else{
        //go up
        datadictpage++;
        if (datadictpage >= dataDictionary.length-1){
          datadictpage = dataDictionary.length-1;
          document.getElementById('ETSuggestionArrowRight').classList.add('invisible');
        }
      }
      if (datadictpage != 0){
        document.getElementById('ETSuggestionArrowLeft').classList.remove('invisible');
      }
      if(datadictpage != dataDictionary.length -1){
        document.getElementById('ETSuggestionArrowRight').classList.remove('invisible');
      }
      document.getElementById('xofindicator').innerHTML = datadictpage+1;
      showET(dataDictionary[datadictpage]);
    }

    if(Object.keys(dataDictionary).length>1){
      var navdisp = document.createElement('p'); 
      var xof = document.createElement('span'); 
      var navBlock1 = document.createElement('span'); 
      var navBlock2 = document.createElement('span'); 
      var navBlock3 = document.createElement('span'); 
      navBlock1.appendChild(document.createTextNode(datadictpage+1));
      navBlock1.setAttribute('id', 'xofindicator'); 
      navBlock2.appendChild(document.createTextNode(' of '));
      navBlock3.appendChild(document.createTextNode(pageLength));
      xof.appendChild(navBlock1);
      xof.appendChild(navBlock2);
      xof.appendChild(navBlock3);
      document.getElementById('etnav').appendChild(navdisp); 
      //create Nav arraow: 
      var prevET = document.createElement('span');
      var nextET = document.createElement('span');
      prevET.appendChild(document.createTextNode('<<'));
      prevET.classList.add('invisible'); 
      prevET.setAttribute('id', 'ETSuggestionArrowLeft'); 
      nextET.appendChild(document.createTextNode('>>')); 
      nextET.setAttribute('id', 'ETSuggestionArrowRight'); 
      navdisp.appendChild(prevET);
      navdisp.appendChild(xof);
      navdisp.appendChild(nextET);
      prevET.addEventListener('click', function(){navET('-')})
      nextET.addEventListener('click', function(){navET('+')})
    }
    let midbox = document.createElement('div'); 
    midbox.classList.add('w-full'); 
  }else{
    //nothing found in the backend: no matching variants or nodelabels: 
    function binVariant(e){
      e.parentElement.remove();
    }
    var createNodeDiv = document.createElement('div'); 
    createNodeDiv.classList.add('w-full'); 
    createNodeDiv.setAttribute('id', 'etcreate'); 
    var embeddedCreateDiv = document.createElement('div'); 
    embeddedCreateDiv.setAttribute('id', 'embeddedET'); 
    var topTex = document.createElement('h3'); 
    topTex.classList.add('uppercase', 'text-xl', 'underline', 'decoration-4', 'underline-offset-2');
    topTex.appendChild(document.createTextNode('Create a new annotation'));
    embeddedCreateDiv.appendChild(topTex); 
    //add code to create a node from selection!
    //append newly created Div to the DOM: 
    targetOfInfo.appendChild(createNodeDiv);
    //dropdown: select the entity type ==> use the color dict available.
    var entityTypeDiv = document.createElement('div');
    var entityTypePrompt = document.createElement('p');
    entityTypePrompt.classList.add('text-lg', 'p-2', 'm-2'); 
    entityTypePrompt.appendChild(document.createTextNode('1) Set entity type: ')); 
    entityTypeDiv.appendChild(entityTypePrompt); 
    var setEntityType = document.createElement('select'); 
    setEntityType.setAttribute('id','entityTypeSelector'); 
    var entityTypeOptionPrompt = document.createElement('option'); 
    entityTypeOptionPrompt.appendChild(document.createTextNode('Entities: '));
    entityTypeOptionPrompt.setAttribute('selected', true);
    entityTypeOptionPrompt.setAttribute('disabled', true); 
    setEntityType.appendChild(entityTypeOptionPrompt); 
    for (var c = 0; c < coreNodes.length; c++){
      var o = document.createElement('option'); 
      o.appendChild(document.createTextNode(coreNodes[c]));
      o.setAttribute('value', coreNodes[c]);
      setEntityType.appendChild(o); 
    }
    embeddedCreateDiv.appendChild(entityTypeDiv);
    embeddedCreateDiv.appendChild(setEntityType);
    createNodeDiv.appendChild(embeddedCreateDiv);
    //Dropdown added: Show positional info: 
    var text = getTextSelection();
    var startPositionInText = text[1];
    var endPositionInText = text[2];
    var selectedString = text[0];
    var positionDiv = document.createElement('div'); 
    positionDiv.setAttribute('id', 'embeddedAnnotation'); 
    var positionTitle = document.createElement('h3'); 
    positionTitle.appendChild(document.createTextNode('Annotation information: ')); 
    setEntityType.addEventListener('change', function(){
      loadPropertiesOfSelectedType(selectedString); 
    })
    //      startposition
    var positionStart = document.createElement('p');
    var positionStartSpan = document.createElement('span');
    var startData = document.createElement('span');
    startData.appendChild(document.createTextNode(startPositionInText));
    positionStartSpan.appendChild(document.createTextNode('Starts: '));
    positionStartSpan.classList.add('font-bold'); 
    positionStart.appendChild(positionStartSpan);
    positionStart.appendChild(startData);
    //      endposition
    var positionEnd = document.createElement('p');
    var positionEndSpan = document.createElement('span');
    var endData = document.createElement('span'); 
    endData.appendChild(document.createTextNode(endPositionInText)); 
    positionEndSpan.appendChild(document.createTextNode('Stops: '));
    positionEndSpan.classList.add('font-bold');
    positionEnd.appendChild(positionEndSpan); 
    positionEnd.appendChild(endData); 
    //    selection
    var selectedText = document.createElement('p');
    var selectedTextSpan = document.createElement('span');
    var textData = document.createElement('span'); 
    textData.appendChild(document.createTextNode(selectedString));
    selectedTextSpan.appendChild(document.createTextNode('Text: '));
    selectedTextSpan.classList.add('font-bold');
    selectedText.appendChild(selectedTextSpan); 
    selectedText.appendChild(textData); 
    //    add it to the block: 
    positionDiv.appendChild(positionTitle);
    positionDiv.appendChild(positionStart);
    positionDiv.appendChild(positionEnd);
    positionDiv.appendChild(selectedText);
    //positional info added: show spellingvariantbox: 
    //    allow the user to generate a list of spelling variants: 
    console.warn("code should be rewritten to use function call to displayWrittenVariants()");
    var spellingVariantTracker = [];
    var spellingVariantMainBox = document.createElement('div');
    spellingVariantMainBox.setAttribute('id', 'embeddedSpellingVariants');
    var spellingVariantTitle = document.createElement('h3'); 
    spellingVariantTitle.appendChild(document.createTextNode('Naming variants: '));
    spellingVariantTitle.classList.add('font-bold', 'text-lg', 'items-center', 'flex', 'justify-center');
    spellingVariantMainBox.appendChild(spellingVariantTitle);
    spellingVariantMainBox.classList.add('border-solid', 'border-2', 'border-black-800', 'rounded-md', 'flex-grow'); 
    var spellingVariantCreation = document.createElement('input'); 
    spellingVariantCreation.setAttribute('id', 'variantInputBox'); 
    spellingVariantCreation.classList.add('border-solid', 'border-2')
    var spellingVariantSubBox = document.createElement('div');
    spellingVariantSubBox.setAttribute('id', 'variantStorageBox'); 
    spellingVariantSubBox.classList.add('flex', 'border-t-2', 'border-t-dashed', 'flex', 'flex-wrap');
    var addToStorageBox = document.createElement('button'); 
    addToStorageBox.appendChild(document.createTextNode('Add')); 
    addToStorageBox.addEventListener('click', function(){
      var writtenValue = document.getElementById('variantInputBox').value; 
      document.getElementById('variantInputBox').value = ''; 
      if(spellingVariantTracker.includes(writtenValue)){
        return;
      }
      spellingVariantTracker.push(writtenValue);
      var storeIn = document.getElementById('variantStorageBox'); 
      var variantDisplayDiv = document.createElement('div'); 
      variantDisplayDiv.classList.add('m-1','p-1','spellingvariantbox', 'bg-amber-100', 'flex');
      var variantDisplayTex = document.createElement('p');
      variantDisplayTex.appendChild(document.createTextNode(writtenValue));
      var variantDisplayBin = document.createElement('p');
      variantDisplayBin.classList.add('xsbinicon', 'bg-amber-200', 'm-1','p-1', 'rounded-full'); 
      variantDisplayBin.addEventListener('click', function(){binVariant(this);});
      variantDisplayDiv.appendChild(variantDisplayTex);
      variantDisplayDiv.appendChild(variantDisplayBin);
      storeIn.appendChild(variantDisplayDiv);
    }); 
    spellingVariantMainBox.appendChild(spellingVariantCreation);
    spellingVariantMainBox.appendChild(addToStorageBox);
    spellingVariantMainBox.appendChild(spellingVariantSubBox);
    //wikidataPrompt: 
    var wikidataQLabel = document.createElement('div');
    wikidataQLabel.setAttribute('readonly', true);
    wikidataQLabel.setAttribute('id', 'chosenQID');
    var wikidataPromptMainbox = document.createElement('div');
    var wikidataInputBox = document.createElement('input');
    wikidataInputBox.setAttribute('id', 'wikidataInputPrompter');
    wikidataInputBox.value = selectedString;
    var searchButtonForWDPrompt = document.createElement('button'); 
    var searchButtonForWDPromptText = document.createTextNode('Search!'); 
    searchButtonForWDPrompt.appendChild(searchButtonForWDPromptText); 
    searchButtonForWDPrompt.addEventListener('click', function(){
      console.log('make function call get the preferred lookup language!'); 
      console.log('lookup and display can be connected!'); 
      wdprompt(wikidataInputBox.value, 0);
    });
    var wikidataResultsBox = document.createElement('div');
    wikidataResultsBox.setAttribute('id', 'wdpromptBox');
    wikidataPromptMainbox.appendChild(wikidataQLabel);
    wikidataPromptMainbox.appendChild(wikidataInputBox);
    wikidataPromptMainbox.appendChild(searchButtonForWDPrompt);
    wikidataPromptMainbox.appendChild(wikidataResultsBox); 


    //add all boxes to the DOM: 
    createNodeDiv.appendChild(positionDiv);
    createNodeDiv.appendChild(spellingVariantMainBox);
    //add a WD Promptbox and trigger the function for wikidata_prompting from here:
    createNodeDiv.appendChild(wikidataPromptMainbox); 
    searchButtonForWDPrompt.click();
    //done with spelling variants: 

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
  div.style.position = 'absolute';
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
  dismiss.addEventListener('click', function(){
    console.log('Close open box');
    ignoreSuggestion();
  });
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
  document.getElementById('suggestionbox_saveButton').disabled = false;
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
  //var coreNodes = ['Place', 'Person', 'Event'];
  var retrievedCoreElements = data.nodes.filter(node => coreNodes.includes(node[1]));
  console.log(retrievedCoreElements);
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
  //insert the search results here:
  triggerSidePanelAction(data);
  datadiv.appendChild(dataOnSearch);
  document.getElementById("suggestionboxspinner").parentNode.insertBefore(datadiv, document.getElementById('suggestionboxspinner'));
  document.getElementById('suggestionboxspinner').remove();
}

let globalSelectionText = null;
let globalSelectionStart = null;
let globalSelectionEnd = null;
function getTextSelection(){
    //you need a map filter on selection based on length of childnodes!
    var selection = rangy.getSelection().getRangeAt(0).getNodes().filter(s => s.childNodes.length == 0);
    if(selection.length > 0){
      //get first and last selection elements to extract data attribute:
      globalSelectionText = rangy.getSelection().toString().trim();
      globalSelectionStart = parseInt(selection[0].parentElement.dataset.itercounter);
      globalSelectionEnd = parseInt(selection[selection.length-1].parentElement.dataset.itercounter);
      return [globalSelectionText, globalSelectionStart, globalSelectionEnd];
    }else{
      return false;
    }
}

function triggerSelection(){
  console.log('triggerSelection function'); 
  var selectedTextProperties = getTextSelection();
  var selectedText = selectedTextProperties[0];
  console.log('Properties: ', selectedTextProperties);
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
  // bug: if cursor lets go off the letter, trigger doesn't work, attach it higher up!
  document.getElementById('textcontent').addEventListener('mouseup', function(){triggerSelection()});
  document.getElementById('textcontent').addEventListener('keyup', function(){triggerSelection()});
  /*
  var triggerpoints = document.getElementsByClassName('ltr');
  for(var i = 0; i < triggerpoints.length; i++){
    if(triggerpoints[i].classList.contains('linked')){
      / * *do not add an event listener if the letter has the linked class (i.e. if it is part of an existing annotation) * /
      continue;
    }
    triggerpoints[i].addEventListener('mouseup', function(e){
      triggerSelection();
    });
    triggerpoints[i].addEventListener('keyup', function(e){
      triggerSelection();
    });
  }*/
  //use esc key to delete the suggestionbox:
  document.addEventListener('keyup', function(event) {
   if (event.key === 'Escape') {
     ignoreSuggestion();
   }
 });
});
