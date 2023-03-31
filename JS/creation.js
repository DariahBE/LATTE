class nodeCreator{
  creationLevel; 
  creationData; 
  coreNodes; 


  constructor(coreDictionary){
    this.coreNodes = coreDictionary; 
    this.createNodeTypeSelector();
  }

  preSubmitCheck(eventhandle){
    console.log(eventhandle); 
    eventhandle.preventDefault(); 
  }

  createFormForType(eventhandle){
    var formTarget = document.getElementById('propertySection');
    formTarget.innerHTML = ''; 
    formTarget.classList.remove('hidden'); 
    var type = eventhandle.srcElement.value
    var form = document.createElement('form');
    form.setAttribute('id', 'inputformSecondStage'); 
    var formGrid = document.createElement('div'); 
    formGrid.classList.add('grid','gap-6', 'mb-6', 'md:grid-cols-2'); 
    console.log('Unused html dom code: saveSection!'); 
    form.classList.add('inputFormForData'); 
    fetch('/AJAX/get_structure.php?type='+type)
      .then((response) => response.json())
      .then((data) =>{
        var keys = Object.keys(data); 
        for(var i=0; i<keys.length; i++){
          var fieldName = 'field_name_'+toString(i); 
          console.log(keys[i]); 
          var attributes = data[keys[i]];
          console.log(data[keys[i]]);
          var oneRowToDOM = document.createElement('div');
          oneRowToDOM.classList.add('form-group');
          var labelForOneRow = document.createElement('label');
          var labelText = document.createTextNode(attributes[0]);
          //label associated with the input field:
          labelForOneRow.appendChild(labelText);
          //textarea field: where user is allowed to enter data.
          var inputField = document.createElement('textarea'); 
          inputField.classList.add('w-full');
          inputField.classList.add('form-control');
          inputField.classList.add('attachValidator')
          inputField.classList.add('validateAs_'+attributes[1].toLowerCase()); 
          inputField.dataset.name=keys[i];
          oneRowToDOM.appendChild(labelForOneRow);
          oneRowToDOM.appendChild(inputField);
          formGrid.appendChild(oneRowToDOM);
        }
        var submit = document.createElement('input');
        submit.setAttribute('type', 'submit');
        submit.addEventListener('click', event => this.preSubmitCheck(event)); 
        formGrid.appendChild(submit)
        form.appendChild(formGrid); 
        formTarget.appendChild(form);
        //attach the vallidator: 
        const vallidation = new Vallidator();
        vallidation.pickup();
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
    for(var i = 0; i < this.coreNodes.length; i++){
      var o = document.createElement('option');
      o.value = this.coreNodes[i];
      o.text = this.coreNodes[i];
      selectBlock.appendChild(o);
    }
    selectBlock.addEventListener('click', event => this.createFormForType(event));
    target.appendChild(selectBlock); 
  }
}
