
function checkPairValidity(value, datatype){
  //TODO: figure out a way of vallidating all datatypes
  return true; 

}

class Validator{

  /*
  globalState(){
    return false;
  }*/

  intValidator(data){
    let shouldBe = parseInt(data)+0; 
    if(shouldBe == data){
      return [true];
    }else{
      return [false, 'The provided input is not a valid real number.'];
    }
  }

  boolValidator(data){
    var value = data.checked; 
    if(typeof value === 'boolean'){
      return[true];
    }else{
      return[false, 'The provided input is not a valid bool.']; 
    }
  }
  
  linkValidator(data){
    try { 
      Boolean(new URL(data.value)); 
      return [true]; 
    }
    catch(e){ 
      return[false, 'The provided URL is invalid.']; 
    }
  }

  floatValidator(data){
    if(!isNaN(parseFloat(data)) && isFinite(data)){
      return [true];
    }else{
      return [false, 'The provided input is not a valid float e.g.: 3.14.'];
    }
  }

  regexValidator(data){
    var pattern = /(^Q\d*$)/;
    if(pattern.test(data)){
      return [true];
    }else{
      return [false, 'Enter a valid Q-identifier: (e.g.: Q1234).'];
    }
  }

  pickup(){
    var elements = document.getElementsByClassName('attachValidator'); 
    console.log(elements); 
    var mainclass = this;
    for(var n=0; n<elements.length; n++){
      var target = elements[n];
      //BUG: system evaluates everything as wikidata entry! // OKAY => new bug integer validator not working.
      //eventlistener is stuck to the last object in the array!
      target.addEventListener('change', async function(){
        console.log(target);
        if(this.classList.contains('validateAs_string')){
          var correct = [true]; //not really required; strings are allowed to be empty anyway!
        }
        if(this.classList.contains('validateAs_longtext')){
          var correct = [true]; //not really required; strings are allowed to be empty anyway!
        }
        if(this.classList.contains('validateAs_wikidata')){
          var correct = mainclass.regexValidator(this.value);
        }
        if(this.classList.contains('validateAs_int')){
          var correct = mainclass.intValidator(this.value);
        }
        if(this.classList.contains('validateAs_bool')){
          var correct = mainclass.boolValidator(this);
        }
        if(this.classList.contains('validateAs_float')){
          var correct = mainclass.floatValidator(this.value);
        }
        if(this.classList.contains('validateAs_uri')){
          var correct = mainclass.linkValidator(this);
        }
        if(this.classList.contains('validateAs_unique')){
          var selectedNode = document.getElementById('nodeTypeSelection').firstChild.value;
          var property = this.getAttribute('data-name');
          // BUG: uniqueness can only handle integers for now!!
          var unique = await fetch('/AJAX/uniqueness.php?nodetype='+selectedNode+'&property='+property+'&value='+this.value)
          .then((response) => response.json())
          .then((data) =>{
            var good = data;
            if(good){
              return [good];
            }else{
              return [false, 'The provided value does not pass the uniqueness constraint set on a database level.']; 
            }
          }); 
          var correctMsg = [];
          if(!correct[0]){
            correctMsg.push(correct[1]); 
          }
          if(!unique[0]){
            correctMsg.push(unique[1]); 
          }
          var correctMsg = correctMsg.join(' and ');
          correct = [correct[0] && unique[0], correctMsg];
        }
        var inFront = this.previousElementSibling; 
        if(inFront && inFront.classList.contains('errorNotification')){
          inFront.remove(); 
        } 
        if (correct[0]){
          this.classList.add('bg-green-50', 'border', 'border-green-500');
          this.classList.remove('bg-red-50', 'border', 'border-red-500', 'validatorFlaggedMistake');
        }else{
          var msg = correct[1];
          var msgHolder = document.createElement('p');
          msgHolder.classList.add('errorNotification');
          var msgText = document.createTextNode(msg); 
          msgHolder.appendChild(msgText);
          this.parentNode.insertBefore(msgHolder, this);
          this.classList.add('bg-red-50', 'border', 'border-red-500', 'validatorFlaggedMistake');
          this.classList.remove('bg-green-50', 'border', 'border-green-500');
        }
      })
    }
  }
}