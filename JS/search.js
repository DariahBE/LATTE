//global
let validator = new Validator;
let labelname;
let searchDict = {
  'node': null,
  'options': {}
}; 

let searchSymbols = {
  int: {
    name : 'number',
    options : {
      '': 'No option supplied; your search will only return results <b>maching</b> the exact number', 
      '>': 'Your search will look for results where the value is <b>larger</b> than the provide number.',
      '>=': 'Your search will look for results where the value is <b>equal or larger</b> than the provide number.',
      '<': 'Your search will look for results where the value is <b>smaller</b> than the provide number.',
      '<=': 'Your search will look for results where the value is <b>equal or smaller</b> than the provide number.',
      '!=': 'Your search will look for results where the value is <b>not equal to</b> the provide number.',
      'x|y': 'Your search will look for results where the value is <b>part of the provided range</b>.'
    } 
  }, 
  string: {
    name : 'text', 
    options : {
      '': 'No option supplied; your search will only return results <b>maching</b> the exact string.', 
      '!=': 'Your search will <b>exclude all values maching</b> the exact string.', 
      '^': 'Your search will <b>ignore capitalization</b> of the provided string.', 
      'word*': 'Your search will look for <b>any string starting</b> with word.', 
      '*word': 'Your search will look for <b>any string ending</b> with word.', 
      '*word*': 'Your search will look for <b>any string containing</b> word.'
    }
  }, 
  wikidata: {
    name: 'Wikidata', 
    options: null
  },
  uri: {
    name: 'Weblink', 
    options: {
      '': 'No option supplied; your search will only return results <b>maching</b> the exact string.', 
      'link*': 'Your search will look for <b>any URL starting</b> with the defined string.', 
      '*link*': 'Your search will look for <b>any URL containing</b> the defined string.'
    }
  }, 
  bool: {
    name: 'Boolean', 
    options: null
  },
  float: {
    name: 'Fractional numbers', 
    options: {
      '': 'No option supplied; your search will only return results <b>maching</b> the exact number', 
      '>': 'Your search will look for results where the value is <b>larger</b> than the provide number.',
      '>=': 'Your search will look for results where the value is <b>equal or larger</b> than the provide number.',
      '<': 'Your search will look for results where the value is <b>smaller</b> than the provide number.',
      '<=': 'Your search will look for results where the value is <b>equal or smaller</b> than the provide number.',
      '!=': 'Your search will look for results where the value is <b>not equal to</b> the provide number.',
      'x|y': 'Your search will look for results where the value is <b>part of the provided range</b>.'
    }
  }
}

function updateDict(){
  let readObject = document.getElementsByClassName('form-block'); 
  console.log(readObject); 
  for(var i = 0; i < readObject.length; i++){
    let currentObject = readObject[i];
    let operator = null; 
    let writtenvalues = []; 
    let name = null; 
    let operatorblock = currentObject.getElementsByClassName('maskOptions'); 
    if(operatorblock.length == 1){
      operator = operatorblock[0].value; 
    }
    let asType = currentObject.getAttribute('data-type');
    name = currentObject.getAttribute('data-name'); 
    let fields = currentObject.getElementsByTagName('input'); 
    for (var j = 0; j<fields.length; j++){
      writtenvalues.push(fields[j].value); 
    }
    searchDict['node'] = labelname; 
    if (!(operator == '' && writtenvalues[0]=='')){
      searchDict['options'].name = {};
      searchDict['options'][name] = {
        'operator': operator,
        'type': asType, 
        'values': writtenvalues
      }; 
    }
  }
  console.log(searchDict);
  $.ajax({
    type: "POST",
    url: "/AJAX/runsearch.php?offset="+offset+"&limit="+limit,
    data: searchDict
})
.done(function( msg ) {                        
     simpleResponseTableGenerator(msg);
})/*
.fail(function(msg) {
     sessionStorage.setItem("success","0");
     window.location.reload();
}); */
  
}

function makeModForRange(){
  let src =  event.target || event.srcElement; 
  let srcvalue = src.value;
  //src holds the dropdown that was modified. 
  let activeInputFields = src.parentElement.getElementsByTagName('input');
  if (srcvalue == 'x|y' && activeInputFields.length == 1){
    //rangemode. 
    src.parentElement.getElementsByTagName('input')[0].classList.remove('w-full');
    //src.parentElement.getElementsByTagName('input')[0].classList.remove('w-full');
    let currentInput = activeInputFields[0]; 
    let rangeBottomLabel = document.createElement('label'); 
    rangeBottomLabel.classList.add('disposableLabel');
    rangeBottomLabel.appendChild(document.createTextNode('From:'));
    let rangeTopLabel = document.createElement('label'); 
    rangeTopLabel.classList.add('disposableLabel');
    rangeTopLabel.appendChild(document.createTextNode('To:'));
    let rangeTop = document.createElement('input'); 
    let nextLine = document.createElement('br'); 
    rangeTop.classList.add('attachValidator', 'validateAs_int'); 
    currentInput.parentNode.insertBefore(rangeBottomLabel, currentInput);
    currentInput.parentNode.insertBefore(rangeTop, currentInput.nextSibling);
    rangeTop.parentNode.insertBefore(rangeTopLabel, currentInput.nextSibling);
    rangeTop.parentNode.insertBefore(nextLine, currentInput.nextSibling);
  }else if(srcvalue != 'x|y' && activeInputFields.length > 1){
    //normal mode. 
    let labels = src.parentElement.getElementsByClassName('disposableLabel');
    while(labels.length > 0){
      labels[0].parentNode.removeChild(labels[0]);
    }
    activeInputFields[1].remove(); 
  }
  validator.pickup(); 
}

function searchInstruction(searchType){
  target = document.getElementById('searchExplain');
  target.innerHTML = '';
  let options = searchSymbols[searchType]['options'];
  if(options){
    let prompt = document.createElement('p');
    prompt.appendChild(document.createTextNode('You can extend the search command by using designated symbols. For '+searchSymbols[searchType]['name']+' fields the following symbols can be used.'));
    prompt.classList.add('py-2', 'my-2');
    target.appendChild(prompt);
    for (const [key, value] of Object.entries(options)){
      let promptBlock = document.createElement('p', 'align-middle');
      promptBlock.classList.add('text-xs', 'w-full', 'h-5', 'align-middle');
      let leftPartPrompt = document.createElement('span');
      let rightPartPrompt = document.createElement('span');
      leftPartPrompt.appendChild(document.createTextNode(key));
      leftPartPrompt.classList.add('bg-gray-300', 'px-1', 'text-center', 'align-middle', 'rounded-md', 'mx-1', 'border', 'border-gray-600', 'min-w-fit' ,'w-7', 'inline-block', 'min-h-fit', 'h-full');
      rightPartPrompt.innerHTML = value;
      rightPartPrompt.classList.add('h-5');
      promptBlock.appendChild(leftPartPrompt);
      promptBlock.appendChild(rightPartPrompt);
      target.appendChild(promptBlock);
    }
  }
}


function loadPropertyBox(on){
  labelname = on.value;
  let searchAbleProperties = searchFields[on.value];
  let target = document.getElementById('field2'); 
  let rangeModifiers = ['int', 'float']; 
  target.innerHTML = ''; 
  console.log(searchAbleProperties);
  for(let i = 0; i < searchAbleProperties.length; i++){
    let prop = searchAbleProperties[i]; 
    let propname = prop[0];
    let propvalidation = prop[1];
    let propPromptName = prop[2]; 
    let propDisplay = document.createElement('div'); 
    let propLabel = document.createElement('label'); 
    propLabel.appendChild(document.createTextNode(propPromptName));
    let searchMaskOptions = document.createElement('select');
    searchMaskOptions.classList.add('maskOptions'); 
    let searchMasks = searchSymbols[propvalidation]['options'];
    if (searchMasks){
      for (const [key, value] of Object.entries(searchMasks)){
        let searchSelectMask = document.createElement('option');
        searchSelectMask.value = key;
        searchSelectMask.text = key;
        searchMaskOptions.appendChild(searchSelectMask); 
      }
    }

    if(rangeModifiers.includes(propvalidation)){
      searchMaskOptions.addEventListener('change', function(){makeModForRange()});
    }
    let fieldDiv = document.createElement('div');
    let fieldValue = document.createElement('input');
    fieldValue.setAttribute('name', propname); 
    fieldValue.classList.add('attachValidator', 'w-full');
    fieldValue.classList.add('validateAs_'+propvalidation); 
    fieldValue.addEventListener('click', function(){searchInstruction(propvalidation)}); 
    propDisplay.appendChild(propLabel);
    if (searchMasks){
      propDisplay.appendChild(searchMaskOptions); 
    }
    fieldDiv.appendChild(fieldValue);
    propDisplay.classList.add('form-group', 'p-2', 'w-full', 'md:w-1/2', 'form-block');
    propDisplay.setAttribute('data-type', propvalidation); 
    propDisplay.setAttribute('data-name', propname); 
    propDisplay.appendChild(fieldDiv);
    target.appendChild(propDisplay);
  }
  //attach validator here: Undo this constructor!
  validator.pickup();
}

function createForm(formElements){
  let keys = Object.keys(formElements); 
  let target = document.getElementById('field1'); 
  for (let i = 0; i < keys.length; i++){
    let k = keys[i];
    let wrapper = document.createElement('div'); 
    wrapper.classList.add('px-8', 'mx-8', 'py-2', 'my-2'); 
    let elemLabel = document.createElement('label');
    elemLabel.appendChild(document.createTextNode(k));
    let elem = document.createElement('input');
    elem.value = k;
    elem.setAttribute('name', 'label');
    elem.setAttribute('type', 'radio');
    elem.addEventListener('click', function(){loadPropertyBox(this)});
    wrapper.appendChild(elemLabel);
    wrapper.appendChild(elem);
    target.appendChild(wrapper);
  }
}

function simpleResponseTableGenerator(data){
  console.log(data);
  let replTable = document.createElement('table'); 
  //let replBody = document.createElement('tbody'); 
  replTable.classList.add('table', 'w-full'); 
  for (const [key, value] of Object.entries(data)) {
    let row = value; 
    console.log(row);
    let rowOut = document.createElement('tr'); 
    rowOut.classList.add('bg-gray-50', 'odd:bg-gray-100', 'hover:bg-gray-200');
    //generate Link to stable URI or TEXT portal
    if(row['stable']){
      var stableLink = document.createElement('i'); 
      stableLink.classList.add('fas', 'fa-anchor');
      var stableLinkURI = document.createElement('a'); 
      stableLinkURI.setAttribute('href', row['stable']); 
      stableLinkURI.setAttribute('target', '_blank');
      stableLinkURI.appendChild(stableLink); 
    }else{
      var stableLinkURI = document.createElement('p'); 
      stableLinkURI.appendChild(document.createTextNode('N/A')); 
    }
    //generate link to graph: 
    let networkImg = document.createElement('img');
    networkImg.src = '/images/graphExplore.png';
    let networkLink = '/explore/'+row['neoid'];
    let networkHref = document.createElement('a');
    networkHref.setAttribute('href', networkLink);
    networkHref.setAttribute('target', '_blank');
    networkHref.appendChild(networkImg); 
    //write links in single cell. 
    let linktd = document.createElement('td'); 
    linktd.appendChild(networkHref);
    linktd.appendChild(stableLinkURI);
    // handle properties: 
    var proplist = document.createElement('ul'); 
    console.log(row['properties']);
    for(var i = 0; i < row['properties'].length; i++){
      console.log('making property row for: '); 
      console.log('rowdata', row['properties'][i]); 
      var proprow = document.createElement('li'); 
      var proprowleft = document.createElement('span'); 
      proprowleft.classList.add('font-bold');
      proprowleft.appendChild(document.createTextNode(row['properties'][i][0]+': ')); 
      var proprowright = document.createElement('span'); 
      if(row['properties'][i][1] == 'uri'){
        var rightLink = document.createElement('a');
        rightLink.setAttribute('href', row['properties'][i][2]);
        rightLink.setAttribute('target', '_blank');
        rightLink.classList.add('externalLink'); 
        rightLink.appendChild(document.createTextNode(row['properties'][i][2])); 
        proprowright.appendChild(rightLink);
      }else if(row['properties'][i][1] == 'wikidata'){
        var rightLink = document.createElement('a');
        rightLink.setAttribute('href', 'https://www.wikidata.org/wiki/'+row['properties'][i][2]);
        rightLink.setAttribute('target', '_blank');
        rightLink.classList.add('externalLink'); 
        rightLink.appendChild(document.createTextNode(row['properties'][i][2])); 
        proprowright.appendChild(rightLink);
      }else{
        proprowright.appendChild(document.createTextNode(row['properties'][i][2]))
      }
      proprow.appendChild(proprowleft); 
      proprow.appendChild(proprowright); 
      console.log(proprow); 
      proplist.appendChild(proprow)
    }
    rowOut.appendChild(linktd); 
    rowOut.appendChild(proplist); 
    replTable.appendChild(rowOut); 
  }
  //console.log(replBody);
  //replTable.appendChild(replBody); 
  console.log(replTable);
  document.getElementById('tableHere').innerHTML = ''; 
  document.getElementById('tableHere').appendChild(replTable); 

}