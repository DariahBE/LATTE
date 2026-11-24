//global

// validator = new Validator;   //NO point in validating search ==> e.g. URL with wildcard. 
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

function resetDict(){
  searchDict = {
    'node': null,
    'options': {}
  }; 
}

function updateDict(){
  let pgTarget = document.getElementById('pgn');
  pgTarget.innerHTML = ''; 
  clearResults();
  resetDict();
  let readObject = document.getElementsByClassName('form-block'); 
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
      if (fields[j].type === 'checkbox'){
        writtenvalues.push(fields[j].checked); 
      }else{
        writtenvalues.push(fields[j].value); 
      }
    }
    searchDict['node'] = labelname; 
    if(operator == null && writtenvalues[0] == ''){
      //IGNORE wikidata search (has NULL as operator) when the search is empty. 
      continue;
    }else if (!(operator == '' && writtenvalues[0]=='')){
      if (searchDict.options && searchDict.options.name) {
        delete searchDict.options.name;
      }
      searchDict['options'][name] = {
        'operator': operator,
        'type': asType, 
        'values': writtenvalues
      }; 
    }
  }
  const formData = new FormData();
  formData.append('node', searchDict.node || '');
  for (const [propName, opt] of Object.entries(searchDict.options || {})) {
    formData.append(`options[${propName}][operator]`, opt.operator);
    formData.append(`options[${propName}][type]`, opt.type);
    if (Array.isArray(opt.values)) {
      opt.values.forEach((val, idx) => {
        formData.append(`options[${propName}][values][${idx}]`, val);
      });
    }
  }

  fetch("/AJAX/runsearch.php?offset=" + offset + "&limit=" + limit, {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(msg => {
    simpleResponseTableGenerator(msg);
  })
  .catch(err => {
    console.error("Search failed:", err);
  });
}

function shortToggler(e){
  let src = e.target || e.srcElement; 
  //check if it is short or long ==> then toggle to the one. 
  if(src.classList.contains('short')){
    src.classList.remove('short'); 
    src.classList.add('orig'); 
    src.textContent = src.dataset.long;
  }else{
    src.classList.add('short'); 
    src.classList.remove('orig'); 
    src.textContent = src.dataset.short; 
  }
}

function makeModForRange(e){
  /**
   * changes DOM to make range-based searches possible (from x to y)
   */
  const evt = e || window.event;
  let src = evt ? (evt.target || evt.srcElement) : null;
  if (!src) {
    return;
  }
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
    // rangeTop.classList.add('attachValidator', 'validateAs_int'); 
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
  // validator.pickup(); 
}

function searchInstruction(searchType){
  /**
   *  inserts explanation with all available search modifiers for the given key 
   */
  let target = document.getElementById('searchExplain');
  target.innerHTML = '';
  let options = searchSymbols[searchType]['options'];
  if(options){
    let promptBox = document.createElement('div');
    promptBox.className = 'p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl text-xs text-slate-600 mb-4';
    
    let promptTitle = document.createElement('p');
    promptTitle.className = 'font-semibold text-slate-700 mb-2';
    promptTitle.textContent = 'Pattern search modifiers for ' + searchSymbols[searchType]['name'] + ' fields:';
    promptBox.appendChild(promptTitle);

    let grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-2';

    for (const [key, value] of Object.entries(options)){
      let item = document.createElement('div');
      item.className = 'flex items-center gap-2';
      let badge = document.createElement('code');
      badge.className = 'px-1.5 py-0.5 bg-white border border-slate-300 rounded font-mono text-indigo-700 text-xs min-w-[2.5rem] text-center font-bold';
      badge.textContent = key === '' ? 'exact' : key;
      let desc = document.createElement('span');
      desc.innerHTML = value;
      item.appendChild(badge);
      item.appendChild(desc);
      grid.appendChild(item);
    }
    promptBox.appendChild(grid);
    target.appendChild(promptBox);
  }
}

function clearOperator(e){
  /**
   * Resets the operator above an input field when the content of the input field has been cleared and left empty!
   */
  if(e.value == ''){
    let selector = e.parentElement.parentElement.getElementsByTagName('select'); 
    if(selector.length > 0){
      selector[0].value = ''; 
    }
  }
}

function loadPropertyBox(on){
  labelname = on.value;
  let searchAbleProperties = searchFields[on.value];
  let target = document.getElementById('field2'); 
  let rangeModifiers = ['int', 'float']; 
  target.innerHTML = ''; 
  for(let i = 0; i < searchAbleProperties.length; i++){
    let prop = searchAbleProperties[i]; 
    let propname = prop[0];
    let propvalidation = prop[1];
    let propPromptName = prop[2]; 
    let propDisplay = document.createElement('div'); 
    propDisplay.className = 'form-group p-3.5 bg-slate-50 border border-slate-200 rounded-xl form-block flex flex-col justify-between';
    propDisplay.setAttribute('data-type', propvalidation); 
    propDisplay.setAttribute('data-name', propname); 

    let headerRow = document.createElement('div');
    headerRow.className = 'flex items-center justify-between mb-2 gap-2';

    let propLabel = document.createElement('label'); 
    propLabel.className = 'text-xs font-semibold uppercase tracking-wider text-slate-600 truncate';
    propLabel.textContent = propPromptName;
    headerRow.appendChild(propLabel);

    let searchMaskOptions = document.createElement('select');
    searchMaskOptions.className = 'maskOptions text-xs bg-white border border-slate-300 rounded-md px-2 py-1 text-slate-700 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500'; 
    let searchMasks = searchSymbols[propvalidation]['options'];
    if (searchMasks){
      for (const [key, value] of Object.entries(searchMasks)){
        let searchSelectMask = document.createElement('option');
        searchSelectMask.value = key;
        searchSelectMask.text = key === '' ? 'Exact' : key;
        searchMaskOptions.appendChild(searchSelectMask); 
      }
      headerRow.appendChild(searchMaskOptions);
    }

    if(rangeModifiers.includes(propvalidation)){
      searchMaskOptions.addEventListener('change', function(e){makeModForRange(e);});
    }

    propDisplay.appendChild(headerRow);

    let fieldDiv = document.createElement('div');
    let fieldValue = document.createElement('input');
    if(propvalidation === 'bool'){
      fieldValue.type = 'checkbox'; 
      fieldValue.className = 'h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
    }else{
      fieldValue.className = 'w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors';
    }
    fieldValue.setAttribute('name', propname); 
    fieldValue.classList.add('validateAs_'+propvalidation); 
    fieldValue.addEventListener('click', function(){searchInstruction(propvalidation)}); 
    fieldValue.addEventListener('change', function(){clearOperator(this)}); 
    fieldDiv.appendChild(fieldValue);
    propDisplay.appendChild(fieldDiv);
    target.appendChild(propDisplay);
  }
  let searchButton = document.getElementById('searchButtonTrigger'); 
  searchButton.removeAttribute('disabled'); 
}

function createForm(formElements){
  let keys = Object.keys(formElements); 
  let target = document.getElementById('field1'); 
  target.innerHTML = '';
  for (let i = 0; i < keys.length; i++){
    let k = keys[i];
    let label = document.createElement('label');
    label.className = 'cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors shadow-sm has-[:checked]:bg-indigo-50 has-[:checked]:border-indigo-500 has-[:checked]:text-indigo-700'; 
    let radio = document.createElement('input');
    radio.value = k;
    radio.setAttribute('name', 'label');
    radio.setAttribute('type', 'radio');
    radio.className = 'h-4 w-4 text-indigo-600 border-slate-300 focus:ring-indigo-500';
    radio.addEventListener('change', function(){loadPropertyBox(this)});
    label.appendChild(radio);
    label.appendChild(document.createTextNode(k));
    target.appendChild(label);
  }
}

function clearResults(){
  let oldResultsContainer = document.getElementById('tableHere'); 
  oldResultsContainer.innerHTML = '';
}

function simpleResponseTableGenerator(data){
  let resultsCard = document.getElementById('resultsCard');
  if (resultsCard) {
    resultsCard.classList.remove('hidden');
  }
  let tableTarget = document.getElementById('tableHere');
  tableTarget.innerHTML = ''; 

  if(Object.keys(data).length == 0){
    if(offset == 0){
      tableTarget.innerHTML = '<div class="text-center py-8 text-slate-500 text-sm">No results match this search criteria.</div>'; 
    }else{
      tableTarget.innerHTML = '<div class="text-center py-8 text-slate-500 text-sm">No additional results found.</div>'; 
    }
  }

  let pgTarget = document.getElementById('pgn');
  pgTarget.innerHTML = ''; 
  pgTarget.className = 'flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200';

  let backButton = document.createElement('button'); 
  backButton.className = 'px-4 py-2 text-sm font-medium bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer';
  backButton.textContent = '← Previous';
  backButton.addEventListener('click', function(){
    offset = offset - limit;
    updateDict();
  });

  let nextButton = document.createElement('button');
  nextButton.className = 'px-4 py-2 text-sm font-medium bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer';
  nextButton.textContent = 'Next →';
  nextButton.addEventListener('click', function(){
    offset = offset + limit; 
    updateDict();
  }); 

  let fromRes = offset + 1; 
  let toRes = offset + Object.keys(data).length; 
  let showingTex = document.createElement('div');
  showingTex.className = 'text-xs font-medium text-slate-500';
  if(Object.keys(data).length > 0){
    showingTex.textContent = 'Showing ' + fromRes + ' to ' + toRes + ' results';
  }

  let navGroup = document.createElement('div');
  navGroup.className = 'flex items-center gap-2';
  if(offset != 0){
    navGroup.appendChild(backButton);
  }
  if(Object.keys(data).length == limit){
    navGroup.appendChild(nextButton);
  }

  pgTarget.appendChild(showingTex); 
  pgTarget.appendChild(navGroup);

  if (Object.keys(data).length > 0) {
    let replTable = document.createElement('table'); 
    replTable.className = 'w-full divide-y divide-slate-200 text-sm'; 

    for (const [key, value] of Object.entries(data)) {
      let row = value; 
      let rowOut = document.createElement('tr'); 
      rowOut.className = 'hover:bg-slate-50/80 transition-colors';

      let linktd = document.createElement('td'); 
      linktd.className = 'py-4 pl-2 pr-4 align-top whitespace-nowrap w-28';
      let btnGroup = document.createElement('div');
      btnGroup.className = 'flex flex-col gap-2';

      let networkLink = '/explore/' + row['neoid'];
      let networkHref = document.createElement('a');
      networkHref.setAttribute('href', networkLink);
      networkHref.setAttribute('target', '_blank');
      networkHref.className = 'inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition-colors';
      networkHref.innerHTML = '<span class="text-xs">☊</span> Explore'; 
      btnGroup.appendChild(networkHref);

      if(row['stable']){
        let stableLinkURI = document.createElement('a'); 
        stableLinkURI.setAttribute('href', row['stable']); 
        stableLinkURI.setAttribute('target', '_blank');
        stableLinkURI.className = 'inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition-colors';
        stableLinkURI.innerHTML = '<span class="text-xs">⚓</span> Stable';
        btnGroup.appendChild(stableLinkURI);
      }

      linktd.appendChild(btnGroup);

      let contenttd = document.createElement('td');
      contenttd.className = 'py-4 px-4 align-top';
      let proplist = document.createElement('ul'); 
      proplist.className = 'space-y-1.5';

      for(let i = 0; i < row['properties'].length; i++){
        let proprow = document.createElement('li'); 
        proprow.className = 'text-xs text-slate-700';
        let proprowleft = document.createElement('span'); 
        proprowleft.className = 'font-semibold text-slate-800 mr-1.5';
        proprowleft.textContent = row['properties'][i][0] + ':'; 
        let proprowright = document.createElement('span'); 

        if(row['properties'][i][1] == 'uri'){
          const rawUri = String(row['properties'][i][2] || '').trim();
          if (/^https?:\/\//i.test(rawUri)) {
            let rightLink = document.createElement('a');
            rightLink.setAttribute('href', rawUri);
            rightLink.setAttribute('target', '_blank');
            rightLink.setAttribute('rel', 'noopener noreferrer');
            rightLink.className = 'text-indigo-600 hover:underline break-all'; 
            rightLink.textContent = rawUri; 
            proprowright.appendChild(rightLink);
          } else {
            let rightText = document.createElement('span');
            rightText.className = 'text-slate-600 break-all';
            rightText.textContent = rawUri;
            proprowright.appendChild(rightText);
          }
        }else if(row['properties'][i][1] == 'wikidata'){
          let rightLink = document.createElement('a');
          rightLink.setAttribute('href', 'https://www.wikidata.org/wiki/' + row['properties'][i][2]);
          rightLink.setAttribute('target', '_blank');
          rightLink.className = 'text-indigo-600 hover:underline inline-flex items-center gap-1 font-mono'; 
          rightLink.textContent = row['properties'][i][2]; 
          proprowright.appendChild(rightLink);
        }else{
          const maxDisplayLength = 200; 
          if (row['properties'][i][3] && row['properties'][i][2].length > maxDisplayLength){
            let shortString = row['properties'][i][2].substring(0, maxDisplayLength) + ' ...'; 
            let originalString = row['properties'][i][2]; 
            proprowright.textContent = shortString;
            proprowright.className = 'short toggleShortLong cursor-pointer text-slate-600 hover:text-slate-900'; 
            proprowright.setAttribute('data-short', shortString);
            proprowright.setAttribute('data-long', originalString);
            proprowright.addEventListener('click', function(e){ shortToggler(e); });
          }else{
            proprowright.textContent = row['properties'][i][2];
          }
        }
        proprow.appendChild(proprowleft); 
        proprow.appendChild(proprowright); 
        proplist.appendChild(proprow);
      }

      let variants = [];
      for(let i = 0; i < row['variants'].length; i++){
        variants.push(row['variants'][i]['label']); 
      }
      if(variants.length > 0){
        let proprow = document.createElement('li'); 
        proprow.className = 'text-xs text-slate-500 pt-1';
        let proprowleft = document.createElement('span'); 
        proprowleft.className = 'font-semibold text-slate-700 mr-1.5';
        proprowleft.textContent = 'Spelling variants:'; 
        let proprowright = document.createElement('span'); 
        proprowright.className = 'italic';
        proprowright.textContent = [...new Set(variants)].join(', '); 
        proprow.appendChild(proprowleft);
        proprow.appendChild(proprowright);
        proplist.appendChild(proprow);
      }

      contenttd.appendChild(proplist);
      rowOut.appendChild(linktd); 
      rowOut.appendChild(contenttd); 
      replTable.appendChild(rowOut); 
    }
    tableTarget.appendChild(replTable); 
  }
}