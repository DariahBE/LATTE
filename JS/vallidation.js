class Vallidator{

  intVallidator(dataField){
    var value = dataField.value
    
  }

  pickup(){
    var elements = document.getElementsByClassName('attachVallidator')
    for(var n=0; n<elements.length(); n++){
      var target = elements[n];
      if(target.classList.contains('validateAs_string')){
        continue; //required??
      }else if(target.classList.contains('validateAs_wikidata')){
        alert('wikidata method to be implementd; ');
      }else if(target.classList.contains('validateAs_int')){
        alert('intmethod to be implemented');
      }else if(target.classList.contains('validateAs_bool')){
        alert('boolmethod to be implemented. '); 
      }
    }
  }

}