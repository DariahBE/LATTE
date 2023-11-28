class nodeCreator{
  creationLevel; 
  creationData; 
  coreNodes; 


  constructor(coreDictionary){
    this.coreNodes = coreDictionary; 
    this.createNodeTypeSelector();
  }

  preSubmitCheck(eventhandle){
    eventhandle.preventDefault();
    //if everything is valid: submit to creation endpoint and create the node!
    var errrormessagesOnScreen = document.getElementsByClassName('errorNotification'); 
    if(errrormessagesOnScreen.length){
      var notification = document.createElement('p'); 
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
    var form = document.getElementById('inputformSecondStage');
    //console.log(nodeType, form);
    /**
      send the form together with the TOKEN here. 
      TODO: send the content of the call to the insert.php page in ajax using the disposable token. 
      BUG: if you drop form as part of the data request, then it works!!! somethinw wrong with the form!! ==> But the receiving page does not show any data coming from the post request!
    */
      fetch("/AJAX/getdisposabletoken.php")
      .then(response => response.json())
      .then(data => {
        const token = data;
        console.log(token); 
        console.log('go', form); 
        const url = "/AJAX/crud/insert.php"; 
        $.ajax({
          type: "POST",
          url: url,
          data: $(form).serialize()
        });
        //BUG: Uncaught (in promise) TypeError: 'checkValidity' called on an object that does not implement interface HTMLTextAreaElement. 
        // don't know why. 

      });   

    
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
        console.log(keys); 
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
    selectBlock.addEventListener('click', event => this.createFormForType(event));
    target.appendChild(selectBlock); 
  }
}
