class nodeCreator{
  creationLevel; 
  creationData; 
  coreNodes; 


  constructor(coreDictionary){
    this.coreNodes = coreDictionary; 
    this.createNodeTypeSelector();
  }

  createFormForType(eventhandle){
    var formTarget = document.getElementById('propertySection');
    formTarget.classList.remove('hidden'); 
    var type = eventhandle.srcElement.value
    console.log(type);
    var form = document.createElement('form'); 
    form.classList.add('inputFormForData'); 
    fetch('/AJAX/get_structure.php?type='+type)
      .then((response) => response.json())
      .then((data) =>{
        var keys = Object.keys(data); 
        for(var i=0; i<keys.length; i++){
          console.log(keys[i]); 
          var attributes = data[keys[i]]; 
          console.log(data[keys[i]]);
          var oneRowToDOM = document.createElement('div'); 
          //label associated with the input field:
          var labelForOneRow = document.createElement('p'); 
          var labelText = document.createTextNode(attributes[0]); 
          labelForOneRow.appendChild(labelText); 
          //input field: where user is allowed to enter data. 
          var inputField = document.createElement('input'); 
          inputField.classList.add('validateAs_'+toString(attributes[1]).toLowerCase(), 'attachVallidator'); 
          inputField.dataset.name=keys[i];
          oneRowToDOM.appendChild(labelForOneRow);
          oneRowToDOM.appendChild(inputField);
          form.appendChild(oneRowToDOM);
        }
        formTarget.appendChild(form);
        //attach the vallidator: 

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
