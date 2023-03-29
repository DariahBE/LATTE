class Vallidator{

  intValidator(data){
    if(Number.isInteger(data)){
      return [true];
    }else{
      return [false, 'The provided input is not a valid real number .'];
    }
  }

  boolValidator(data){
    var value = data; 
    console.log(value);
  }

  floatValidator(data){
    alert('What about negative floats!'); 
    var pattern = /(^\d*.\d*$)/
    var value = data; 
    if(pattern.test(data)){
      return [true];
    }else{
      return [false, 'The provided input is not a valid float e.g.: 3.14 .'];
    }
  }

  regexValidator(data){
    console.log(data); 
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
    for(var n=0; n<elements.length; n++){
      var target = elements[n];
      var mainclass = this;
      target.addEventListener('change', function(){
        if(target.classList.contains('validateAs_string')){
          var correct = ''; //not really required; strings are allowed to be empty anyway!
        }else if(target.classList.contains('validateAs_wikidata')){
          var correct = mainclass.regexValidator(this.value);
        }else if(target.classList.contains('validateAs_int')){
          var correct = mainclass.intValidator(this.value);
        }else if(target.classList.contains('validateAs_bool')){
          var correct = mainclass.boolValidator(this.value);
        }else if(target.classList.contains('validateAs_float')){
          var correct = mainclass.floatValidator(this.value);
        }
        if (correct[0]){
          var inFront = this.previousElementSibling; 
          if(inFront.classList.contains('errorNotification')){
            inFront.remove(); 
          } 
          this.classList.add('bg-green-50', 'border', 'border-green-500');
          this.classList.remove('bg-red-50', 'border', 'border-red-500');
        }else{
          var msg = correct[1];
          var msgHolder = document.createElement('p');
          msgHolder.classList.add('errorNotification');
          var msgText = document.createTextNode(msg); 
          msgHolder.appendChild(msgText);
          this.parentNode.insertBefore(msgHolder, this);
          this.classList.add('bg-red-50', 'border', 'border-red-500');
          this.classList.remove('bg-green-50', 'border', 'border-green-500');
        }
      })
    }
  }

}