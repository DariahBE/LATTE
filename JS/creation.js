class nodeCreator{
  creationLevel; 
  creationData; 
  coreNodes; 


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
    that simply prevents making DOM-elements to query/ put data
    * /
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
    //if everything is valid: submit to creation endpoint and create the node!
    var errrormessagesOnScreen = document.getElementsByClassName('errorNotification'); 
    if(errrormessagesOnScreen.length){
      var notification = document.createElement('p'); 
      notification.setAttribute('id', 'detectedPresentError'); 
      var notificationText = document.createTextNode('One or more properties have an invalid value. Data was not submitted.');
      notification.appendChild(notificationText); 
      document.getElementById('formMessageBox').appendChild(notification);
      return; 
    }else{
      document.getElementById('formMessageBox').innerHTML = ''; 
    }
    //presubmit check passed: send the form: 
    //make it return the stable URI for the newly created node if possible. 
    //backend should also validate the input!
    var nodeType = document.getElementById('nodeTypeSelection').firstChild.value;
    var formcontent = document.getElementById('inputformSecondStage').getElementsByClassName('form-group');
    let submissiondata = {'formdata': {}, 'etype': nodeType, 'token': null};
    for (let j = 0; j < formcontent.length; j++){
      let group = formcontent[j]; 
      let group_box = group.getElementsByClassName('attachValidator')[0]; 
      let group_name = group_box.getAttribute('data-name');
      let group_value = group_box.value; 
      submissiondata['formdata'][group_name] = group_value; 
    }

     
    fetch("/AJAX/getdisposabletoken.php")
      .then(response => response.json())
      .then(data => {
        const token = data;
        const url = "/AJAX/crud/insert.php"; 
        submissiondata['token'] = token; 
        $.ajax({
          type: "POST",
          url: url,
          data: submissiondata, 
          success: (e) => {
            this.reset();
            let result = document.createElement('p'); 
            if(e.hasOwnProperty('stable') && Array.isArray(e['stable']) && e['stable'].length > 0){
              let resultText = document.createTextNode('A new entry was successfully added to the database. You can access this new node here: '); 
              let resultSubLink= document.createElement('a');
              let resultSubLinkText = document.createTextNode(e['stable'][0]);
              resultSubLink.setAttribute('href', e['stable'][0]);
              resultSubLink.appendChild(resultSubLinkText); 
              result.appendChild(resultText); 
              result.appendChild(resultSubLink); 
              result.classList.add('border-green-500'); 
              document.getElementById('formMessageBox').appendChild(result);  
            }else{
              let resultText = document.createTextNode('This element could not be added to the database.');
              result.appendChild(resultText);
              document.getElementById('formMessageBox').appendChild(result);
            } 
          },
          dataType: "JSON"    // datatype as optional parameter. 
        });
      });
  }



  

  createFormForType(eventhandle){
    /**
     * Creates the form to create a new node. When a text node is created it'll change the layout of the form
     * so that the field which contains the text property is a textarea instead of a text input. 
     * 
     */
    var formTarget = document.getElementById('propertySection');
    formTarget.innerHTML = ''; 
    formTarget.classList.remove('hidden'); 
    var type = eventhandle.srcElement.value;
    var form = document.createElement('form');
    form.setAttribute('id', 'inputformSecondStage'); 
    var formGrid = document.createElement('div'); 
    formGrid.classList.add('grid','gap-6', 'mb-6', 'md:grid-cols-2'); 
    form.classList.add('inputFormForData'); 
    var textproperty = false;
    if(type == 'false'){
      return;
    }else if (type === texnode){
      textproperty = texnodetext; 
      var ikey = null; 
    }

    fetch('/AJAX/get_structure.php?type='+type)
      .then((response) => response.json())
      .then((data) =>{
        var keys = Object.keys(data['data']); 
        data = data['data'];
        for(var i=0; i<keys.length; i++){
          if(textproperty && keys[i]==textproperty){
            //don't add the TEXNODETEXT content in between the bulk of the properties. 
            ikey = i; 
            continue;
          }
          //var fieldName = 'field_name_'+toString(i); 
          var attributes = data[keys[i]];
          var oneRowToDOM = document.createElement('div');
          oneRowToDOM.classList.add('form-group');
          var labelForOneRow = document.createElement('label');
          var labelText = document.createTextNode(attributes[0]);
          var uniqueness = attributes[2]; 
          //label associated with the input field:
          labelForOneRow.appendChild(labelText);
          //textarea field: where user is allowed to enter data.
          var inputField = document.createElement('textarea'); 
          inputField.classList.add('w-full');
          inputField.classList.add('form-control');
          inputField.classList.add('attachValidator');
          if(uniqueness){
            //test if uniqueness class is part of the DOM: Test Passed
            inputField.classList.add('validateAs_unique');
          }
          inputField.classList.add('validateAs_'+attributes[1].toLowerCase());
          inputField.dataset.name=keys[i];
          oneRowToDOM.appendChild(labelForOneRow);
          oneRowToDOM.appendChild(inputField);
          formGrid.appendChild(oneRowToDOM);
        }
        form.appendChild(formGrid); 
        if(textproperty){
          //instead put it in a separate row and make it full width!
          //Also increase the height of it then!
          var attributes = data[keys[ikey]];
          var oneRowToDOM = document.createElement('div');
          oneRowToDOM.classList.add('w-full', 'form-group', 'col-span-12');
          var labelForOneRow = document.createElement('label');
          var labelText = document.createTextNode(attributes[0]);
          var uniqueness = attributes[2]; 
          //label associated with the input field:
          labelForOneRow.appendChild(labelText);
          //textarea field: where user is allowed to enter data.
          var inputField = document.createElement('textarea'); 
          inputField.setAttribute('rows', 10);
          inputField.classList.add('w-full');
          inputField.classList.add('form-control');
          inputField.classList.add('attachValidator');
          if(uniqueness){
            //test if uniqueness class is part of the DOM: Test Passed
            inputField.classList.add('validateAs_unique');
          }
          inputField.classList.add('validateAs_'+attributes[1].toLowerCase());
          inputField.dataset.name=keys[ikey];
          oneRowToDOM.appendChild(labelForOneRow);
          oneRowToDOM.appendChild(inputField);
          form.appendChild(oneRowToDOM);
        }
        var submit = document.createElement('input');
        submit.setAttribute('type', 'submit');
        submit.addEventListener('click', event => this.preSubmitCheck(event)); 
        submit.classList.add('btn','bg-blue-400', 'm-2', 'p-2', 'rounded-sm'); 
        form.appendChild(submit);
        formTarget.appendChild(form);
        const validation = new Validator;
        validation.pickup();
      });
  }

  createNodeTypeSelector(){
    var target = document.getElementById('nodeTypeSelection');
    target.innerHTML = '';
    var selectBlock = document.createElement('select');
    selectBlock.setAttribute('id', 'select_dd_element'); 
    var prompt = document.createElement('option');
    prompt.setAttribute('disabled', 1);
    prompt.setAttribute('selected', 1);
    prompt.text = "Select Node Type";
    prompt.value = false;
    selectBlock.appendChild(prompt);
    for(var i = 0; i < Object.keys(this.coreNodes).length; i++){
      var o = document.createElement('option');
      o.value = this.coreNodes[i];
      o.text = this.coreNodes[i];
      selectBlock.appendChild(o);
    }
    selectBlock.addEventListener('change', event => this.createFormForType(event));
    target.appendChild(selectBlock); 
  }
}
