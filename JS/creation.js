class nodeCreator{
  creationLevel; 
  creationData; 
  coreNodes; 


  constructor(coreDictionary){
    this.coreNodes = coreDictionary; 
    this.createNodeTypeSelector();
    this.reset = this.reset.bind(this);

  }

  reset(){
    alert('resetting'); 
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
      //this.test(4);   //OK

        const token = data;
        const url = "/AJAX/crud/insert.php"; 
        submissiondata['token'] = token; 
        $.ajax({
          type: "POST",
          url: url,
          data: submissiondata, 
          success: (e) => {
            this.reset();
            console.log(e);
          },
          
          dataType: "JSON"    // datatype as optional parameter. 
        });
      });
  }

  test(a){
    //TODO: delete this
    console.warn('Testfunction call', a); 
  }

  createFormForType(eventhandle){
    var formTarget = document.getElementById('propertySection');
    formTarget.innerHTML = ''; 
    formTarget.classList.remove('hidden'); 
    var type = eventhandle.srcElement.value;
    var form = document.createElement('form');
    form.setAttribute('id', 'inputformSecondStage'); 
    var formGrid = document.createElement('div'); 
    formGrid.classList.add('grid','gap-6', 'mb-6', 'md:grid-cols-2'); 
    form.classList.add('inputFormForData'); 
    if(type == 'false'){return;}
    fetch('/AJAX/get_structure.php?type='+type)
      .then((response) => response.json())
      .then((data) =>{
        var keys = Object.keys(data['data']); 
        data = data['data'];
        for(var i=0; i<keys.length; i++){
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
            inputField.classList.add('validateAs_unique');
          }
          inputField.classList.add('validateAs_'+attributes[1].toLowerCase());
          inputField.dataset.name=keys[i];
          oneRowToDOM.appendChild(labelForOneRow);
          oneRowToDOM.appendChild(inputField);
          formGrid.appendChild(oneRowToDOM);
        }
        var submit = document.createElement('input');
        submit.setAttribute('type', 'submit');
        submit.addEventListener('click', event => this.preSubmitCheck(event)); 
        formGrid.appendChild(submit);
        form.appendChild(formGrid); 
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
