//global
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
      'word*': 'Your search will look for any string starting with word.', 
      '*word': 'Your search will look for any string ending with word.', 
      '*word*': 'Your search will look for any string containing word.'
    }
  }
}


function searchInstruction(searchType){
  //alert(searchType); 
 target = document.getElementById('searchExplain'); 
  target.innerHTML = ''; 
  let prompt = document.createElement('p'); 
  prompt.appendChild(document.createTextNode('You can extend the search command by using designated symbols. For '+searchSymbols[searchType]['name']+' fields the following symbols can be used.'))
  prompt.classList.add('py-2', 'my-2'); 
  target.appendChild(prompt);
  let options = searchSymbols[searchType]['options']; 
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

function loadPropertyBox(on){
  //console.log(on.value); 
  let searchAbleProperties = searchFields[on.value];
  let target = document.getElementById('field2'); 
  target.innerHTML = ''; 
  
  //console.log(searchAbleProperties);
  for(let i = 0; i < searchAbleProperties.length; i++){
    let prop = searchAbleProperties[i]; 
    let propname = prop[0];
    let propvalidation = prop[1];
    let propPromptName = prop[2]; 
    let propDisplay = document.createElement('div'); 
    let propLabel = document.createElement('label'); 
    propLabel.appendChild(document.createTextNode(propPromptName));
    let searchMaskOptions = document.createElement('select'); 
    let searchMasks = searchSymbols[propvalidation]['options']; 
    for (const [key, value] of Object.entries(searchMasks)){
      let searchSelectMask = document.createElement('option'); 
      searchSelectMask.value = key; 
      searchSelectMask.text = key; 
      searchMaskOptions.appendChild(searchSelectMask); 
    }

    let fieldValue = document.createElement('input');
    fieldValue.setAttribute('name', propname); 
    fieldValue.classList.add('attachValidator', 'w-full');
    fieldValue.classList.add('validateAs_'+propvalidation); 
    fieldValue.addEventListener('click', function(){searchInstruction(propvalidation)}); 
    propDisplay.appendChild(propLabel);
    propDisplay.appendChild(searchMaskOptions); 
    propDisplay.appendChild(fieldValue);
    propDisplay.classList.add('form-group', 'p-2', 'w-full', 'md:w-1/2');
    target.appendChild(propDisplay);
  }

  //attach validator here: 
  let validator = new Validator('dataSearch');
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