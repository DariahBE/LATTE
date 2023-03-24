class nodeCreator{
  creationLevel; 
  creationData; 
  coreNodes; 


  constructor(coreDictionary){
    this.coreNodes = coreDictionary; 
    this.createNodeTypeSelector();
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
    target.appendChild(selectBlock); 
  }
}
