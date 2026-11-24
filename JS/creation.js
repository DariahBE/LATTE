class nodeCreator{
  creationLevel; 
  creationData; 
  coreNodes; 
  //validator class lifted into global scope. 
  validator; 


  constructor(coreDictionary){
    this.coreNodes = coreDictionary; 
    this.createNodeTypeSelector();
    this.reset = this.reset.bind(this);

  }
  nonloginDetected(){
    // RUN MOD ON: ?
    //call whenever nonlogin is detected from calls to get_structure.php

    /*modifies the DOM to hide elements that require login
    NOTE: this is not a security feature. Data that requires
    sessions are protected serverside. This is clientside code
    that simply prevents making DOM-elements to query/ put data*/
    
  }

  reset(){
    /**
     * Resets the node creation form. 
     */
    let present_error = document.getElementById('detectedPresentError'); 
    if(present_error !== null){
      present_error.remove(); 
    }
    document.getElementById('propertySection').innerHTML = '';      //clears the div itself. 
    document.getElementById('select_dd_element').selectedIndex  = 0;    //resets the selector. 
  }

  preSubmitCheck(eventhandle){
    eventhandle.preventDefault();
    const form = document.getElementById('inputformSecondStage');
    if (form && !form.reportValidity()) {
      return;
    }
    if (typeof validator !== 'undefined' && validator && typeof validator.checkRequired === 'function') {
      validator.checkRequired();
    }
    //if everything is valid: submit to creation endpoint and create the node!
    let errrormessagesOnScreen = document.getElementsByClassName('errorNotification'); 
    let flaggedMistakes = document.getElementsByClassName('validatorFlaggedMistake');
    if(errrormessagesOnScreen.length || flaggedMistakes.length){
      document.getElementById('formMessageBox').innerHTML = `
        <div id="detectedPresentError" class="p-4 mb-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center gap-3 shadow-sm">
          <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs">!</span>
          <p class="text-sm font-medium">One or more properties have an invalid or missing required value. Data was not submitted.</p>
        </div>
      `;
      return; 
    }else{
      document.getElementById('formMessageBox').innerHTML = ''; 
    }
    //presubmit check passed: send the form: 
    //make it return the stable URI for the newly created node if possible. 
    //backend should also validate the input!
    let nodeType = document.getElementById('nodeTypeSelection').firstChild.value;
    let formcontent = document.getElementById('inputformSecondStage').getElementsByClassName('form-group');
    let submissiondata = {'formdata': {}, 'etype': nodeType, 'token': null};
    for (let j = 0; j < formcontent.length; j++){
      let group = formcontent[j]; 
      let group_box = group.getElementsByClassName('attachValidator')[0]; 
      let group_name = group_box.getAttribute('data-name');
      let group_value = group_box.type === 'checkbox' ? group_box.checked : group_box.value; 
      submissiondata['formdata'][group_name] = group_value; 
    }

    fetch("/AJAX/getdisposabletoken.php")
      .then(response => response.json())
      .then(token => {
        const url = "/AJAX/crud/insert.php";
        const formData = new FormData();
        formData.append('token', token);
        formData.append('etype', nodeType);
        for (const [k, v] of Object.entries(submissiondata['formdata'])) {
          formData.append('formdata[' + k + ']', v);
        }

        return fetch(url, {
          method: 'POST',
          body: formData
        });
      })
      .then(response => response.json())
      .then(e => {
        this.reset();
        const stableUri = (e.hasOwnProperty('stable') && Array.isArray(e['stable']) && e['stable'].length > 0) 
          ? e['stable'][0] 
          : (e.hasOwnProperty('stable') && typeof e['stable'] === 'string' && e['stable'].length > 0 ? e['stable'] : false);
        if(stableUri){
          document.getElementById('formMessageBox').innerHTML = `
            <div class="p-4 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
              <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">✓</span>
                <div>
                  <p class="font-semibold text-sm">Entity created successfully</p>
                  <p class="text-xs text-emerald-700 break-all">${stableUri}</p>
                </div>
              </div>
              <a href="${stableUri}" class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors whitespace-nowrap">
                View entity
              </a>
            </div>
          `;
        }else{
          document.getElementById('formMessageBox').innerHTML = `
            <div class="p-4 mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center gap-3 shadow-sm">
              <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs">!</span>
              <p class="text-sm font-medium">This element could not be added to the database.</p>
            </div>
          `;
        } 
      })
      .catch(() => {
        document.getElementById('formMessageBox').innerHTML = `
          <div class="p-4 mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center gap-3 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs">!</span>
            <p class="text-sm font-medium">This element could not be added to the database.</p>
          </div>
        `;
      });
  }

  createFormForType(eventhandle){
    /**
     * Creates the form to create a new node. When a text node is created it will change the layout of the form
     * so that the field which contains the text property is a textarea instead of a text input. 
     */
    let formTarget = document.getElementById('propertySection');
    formTarget.innerHTML = ''; 
    formTarget.classList.remove('hidden'); 
    let type = eventhandle.srcElement.value;
    let form = document.createElement('form');
    form.setAttribute('id', 'inputformSecondStage'); 
    let formGrid = document.createElement('div'); 
    formGrid.classList.add('grid', 'gap-6', 'mb-6', 'md:grid-cols-2'); 
    form.classList.add('inputFormForData'); 
    let textproperty = false;
    if(type == 'false'){
      return;
    }else if (type === texnode){
      textproperty = texnodetext; 
      var ikey = null; 
    }

    fetch('/AJAX/get_structure.php?type='+type)
      .then((response) => response.json())
      .then((data) =>{
        let keys = Object.keys(data['data']); 
        data = data['data'];
        for(let i=0; i<keys.length; i++){
          if(textproperty && keys[i]==textproperty){
            ikey = i; 
            continue;
          }
          let attributes = data[keys[i]];
          let oneRowToDOM = document.createElement('div');
          oneRowToDOM.classList.add('form-group');
          let labelForOneRow = document.createElement('label');
          labelForOneRow.classList.add('block', 'text-sm', 'font-medium', 'text-slate-700', 'mb-1.5');
          let uniqueness = attributes[2]; 
          let propType = attributes[1].toLowerCase();

          if(uniqueness){
            labelForOneRow.innerHTML = attributes[0] + ' <span class="text-rose-500">*</span> <span class="text-xs text-slate-400 font-normal">(unique)</span>';
          }else{
            labelForOneRow.textContent = attributes[0];
          }

          let inputField;
          if(propType === 'int' || propType === 'float'){
            inputField = document.createElement('input');
            inputField.type = 'number';
            if(propType === 'float'){
              inputField.step = 'any';
            }
            inputField.classList.add('w-full', 'px-3.5', 'py-2.5', 'bg-white', 'border', 'border-slate-300', 'rounded-lg', 'shadow-sm', 'text-slate-800', 'text-sm', 'focus:ring-2', 'focus:ring-indigo-500', 'focus:border-indigo-500', 'transition-colors', 'attachValidator');
          }else if(propType === 'uri'){
            inputField = document.createElement('input');
            inputField.type = 'url';
            inputField.placeholder = 'https://...';
            inputField.classList.add('w-full', 'px-3.5', 'py-2.5', 'bg-white', 'border', 'border-slate-300', 'rounded-lg', 'shadow-sm', 'text-slate-800', 'text-sm', 'focus:ring-2', 'focus:ring-indigo-500', 'focus:border-indigo-500', 'transition-colors', 'attachValidator');
          }else if(propType === 'bool'){
            inputField = document.createElement('input');
            inputField.type = 'checkbox';
            inputField.classList.add('h-5', 'w-5', 'rounded', 'border-slate-300', 'text-indigo-600', 'focus:ring-indigo-500', 'attachValidator');
          }else if(propType === 'wikidata'){
            inputField = document.createElement('input');
            inputField.type = 'text';
            inputField.placeholder = 'Q...';
            inputField.classList.add('w-full', 'px-3.5', 'py-2.5', 'bg-white', 'border', 'border-slate-300', 'rounded-lg', 'shadow-sm', 'text-slate-800', 'text-sm', 'focus:ring-2', 'focus:ring-indigo-500', 'focus:border-indigo-500', 'transition-colors', 'attachValidator');
          }else{
            inputField = document.createElement('input');
            inputField.type = 'text';
            inputField.classList.add('w-full', 'px-3.5', 'py-2.5', 'bg-white', 'border', 'border-slate-300', 'rounded-lg', 'shadow-sm', 'text-slate-800', 'text-sm', 'focus:ring-2', 'focus:ring-indigo-500', 'focus:border-indigo-500', 'transition-colors', 'attachValidator');
          }

          if(uniqueness){
            inputField.classList.add('validateAs_unique');
            inputField.required = true; 
          }
          inputField.classList.add('validateAs_'+propType);
          inputField.dataset.name = keys[i];
          oneRowToDOM.appendChild(labelForOneRow);
          oneRowToDOM.appendChild(inputField);
          formGrid.appendChild(oneRowToDOM);
        }
        form.appendChild(formGrid); 
        if(textproperty && ikey !== null){
          let attributes = data[keys[ikey]];
          let oneRowToDOM = document.createElement('div');
          oneRowToDOM.classList.add('w-full', 'form-group', 'col-span-12', 'mb-6');
          let labelForOneRow = document.createElement('label');
          labelForOneRow.classList.add('block', 'text-sm', 'font-medium', 'text-slate-700', 'mb-1.5');
          let uniqueness = attributes[2]; 
          if(uniqueness){
            labelForOneRow.innerHTML = attributes[0] + ' <span class="text-rose-500">*</span> <span class="text-xs text-slate-400 font-normal">(unique)</span>';
          }else{
            labelForOneRow.textContent = attributes[0];
          }
          let inputField = document.createElement('textarea'); 
          inputField.setAttribute('rows', 8);
          inputField.classList.add('w-full', 'px-3.5', 'py-2.5', 'bg-white', 'border', 'border-slate-300', 'rounded-lg', 'shadow-sm', 'text-slate-800', 'font-mono', 'text-sm', 'leading-relaxed', 'focus:ring-2', 'focus:ring-indigo-500', 'focus:border-indigo-500', 'transition-colors', 'attachValidator');
          if(uniqueness){
            inputField.classList.add('validateAs_unique');
            inputField.required = true; 
          }
          inputField.classList.add('validateAs_'+attributes[1].toLowerCase());
          inputField.dataset.name = keys[ikey];
          oneRowToDOM.appendChild(labelForOneRow);
          oneRowToDOM.appendChild(inputField);
          form.appendChild(oneRowToDOM);
        }
        let submit = document.createElement('button');
        submit.setAttribute('type', 'submit');
        submit.textContent = 'Save entity';
        submit.addEventListener('click', event => this.preSubmitCheck(event)); 
        submit.classList.add('px-6', 'py-2.5', 'bg-indigo-600', 'hover:bg-indigo-700', 'text-white', 'font-medium', 'text-sm', 'rounded-lg', 'shadow-sm', 'transition-colors', 'cursor-pointer'); 
        form.appendChild(submit);
        formTarget.appendChild(form);
        validator = new Validator;
        validator.set_id_of_dropdown_element_for_entity('select_dd_element');
        validator.set_autofetch_to(true);
        validator.pickup();
      });
  }

  createNodeTypeSelector(){
    let target = document.getElementById('nodeTypeSelection');
    target.innerHTML = '';
    let selectBlock = document.createElement('select');
    selectBlock.setAttribute('id', 'select_dd_element'); 
    selectBlock.classList.add('w-full', 'sm:w-80', 'px-3.5', 'py-2.5', 'bg-white', 'border', 'border-slate-300', 'rounded-lg', 'shadow-sm', 'text-slate-700', 'text-sm', 'font-medium', 'focus:ring-2', 'focus:ring-indigo-500', 'focus:border-indigo-500', 'transition-colors');
    let prompt = document.createElement('option');
    prompt.setAttribute('disabled', 1);
    prompt.setAttribute('selected', 1);
    prompt.text = "Select entity type";
    prompt.value = false;
    selectBlock.appendChild(prompt);
    for(let i = 0; i < Object.keys(this.coreNodes).length; i++){
      let o = document.createElement('option');
      o.value = this.coreNodes[i];
      o.text = this.coreNodes[i];
      selectBlock.appendChild(o);
    }
    selectBlock.addEventListener('change', event => this.createFormForType(event));
    target.appendChild(selectBlock); 
  }
}
