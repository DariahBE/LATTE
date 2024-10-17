
class Validator{

  checkRequired(){
    //required is set to all elements which have the unique attribute
    /**
     * Called before sumbit event: you need this because the change event
     * is never triggered when a user skips over required fields!.
     */
    let unique_elements = document.getElementsByClassName('validateAs_unique'); 
    for (let i = 0; i < unique_elements.length; i++) {
      let elm = unique_elements[i];
      if (elm.value == '') {
        var msgHolder = document.createElement('p');
        msgHolder.classList.add('errorNotification');
        var msgText = document.createTextNode('An empty value was given to a unique-field. Provide a unique value.'); 
        msgHolder.appendChild(msgText);
        elm.parentNode.insertBefore(msgHolder, elm);
        elm.classList.add('bg-red-50', 'border', 'border-red-500', 'validatorFlaggedMistake');
        elm.classList.remove('bg-green-50', 'border', 'border-green-500');
      }
    }
  }

  reset(){
    let e = event.src || event.target; 
    e.classList.remove('validatorFlaggedMistake'); 
    e.classList.remove('bg-red-50');
    e.classList.remove('bg-green-50');
    e.classList.remove('border-red-500'); 
    e.classList.remove('border-green-500'); 
    e.classList.add('border-gray-300', 'text-gray-900', 'border'); 
  }


  intValidator(data){
    // BUG low priority: it is possible to enter alphabetic characters in a number field. 
    // this does not trigger a valid check though! non-numeric characters will always render
    // data to '' an empty string, which is not a problem.
    data = data.value; 
    if(data == ''){this.reset(); return[];}
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
    if (data.value == ''){this.reset(); return [];}
    try { 
      Boolean(new URL(data.value)); 
      return [true]; 
    }
    catch(e){ 
      return[false, 'The provided URL is invalid.']; 
    }
  }

  floatValidator(data){
    data = data.value; 
    if (data == ''){this.reset(); return [];}
    if(!isNaN(parseFloat(data)) && isFinite(data)){
      return [true];
    }else{
      return [false, 'The provided input is not a valid float e.g.: 3.14.'];
    }
  }

  regexValidator(data){
    if (data == ''){this.reset(); return [];}
    var pattern = /(^Q\d+$)/;
    if(pattern.test(data)){
      return [true];
    }else{
      return [false, 'Enter a valid Q-identifier: (e.g.: Q1234).'];
    }
  }
  pickup(){
    var elements = document.getElementsByClassName('attachValidator'); 
    var mainclass = this;
    for(var n=0; n<elements.length; n++){
      var target = elements[n];
      target.addEventListener('change', async function(){
        // console.log("detected change");
        if(this.classList.contains('validateAs_string')){
          if(this.value == ''){
            mainclass.reset(); return [];
          }else{
            var correct = [true]; //not really required; strings are allowed to be empty anyway!
          }
        }
        /*    //longtext is dropped as a type!
        if(this.classList.contains('validateAs_longtext')){
          var correct = [true]; //not really required; strings are allowed to be empty anyway!
        }*/
        if(this.classList.contains('validateAs_wikidata')){
          var correct = mainclass.regexValidator(this.value);
        }
        if(this.classList.contains('validateAs_int')){
          var correct = mainclass.intValidator(this);
        }
        if(this.classList.contains('validateAs_bool')){
          var correct = mainclass.boolValidator(this);
        }
        if(this.classList.contains('validateAs_float')){
          var correct = mainclass.floatValidator(this);
        }
        if(this.classList.contains('validateAs_uri')){
          var correct = mainclass.linkValidator(this);
        }
        if (correct.length != 0){
          if(this.classList.contains('validateAs_unique')){
          var selectedNode; 
            if (this.hasAttribute('data-nodetype_override')) {
              selectedNode = this.getAttribute('data-nodetype_override');
            }else{
              selectedNode = document.getElementById('nodeTypeSelection').firstChild.value;
            }
            var property = this.getAttribute('data-name');
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
        }
      })
    }
  }
}



// REFACTORED CLASS - Work in progress.

// class Validator {
  
//   checkRequired() {
//     let unique_elements = document.getElementsByClassName('validateAs_unique'); 
//     for (let i = 0; i < unique_elements.length; i++) {
//       let elm = unique_elements[i];
//       if (elm.value == '') {
//         var msgHolder = document.createElement('p');
//         msgHolder.classList.add('errorNotification');
//         var msgText = document.createTextNode('An empty value was given to a unique-field. Provide a unique value.');
//         msgHolder.appendChild(msgText);
//         elm.parentNode.insertBefore(msgHolder, elm);
//         elm.classList.add('bg-red-50', 'border', 'border-red-500', 'validatorFlaggedMistake');
//         elm.classList.remove('bg-green-50', 'border', 'border-green-500');
//       }
//     }
//   }

//   reset() {
//     let e = event.src || event.target; 
//     e.classList.remove('validatorFlaggedMistake'); 
//     e.classList.remove('bg-red-50');
//     e.classList.remove('bg-green-50');
//     e.classList.remove('border-red-500'); 
//     e.classList.remove('border-green-500'); 
//     e.classList.add('border-gray-300', 'text-gray-900', 'border'); 
//   }

//   intValidator(data) {
//     data = data.value; 
//     if(data == '') { this.reset(); return []; }
//     let shouldBe = parseInt(data) + 0; 
//     if(shouldBe == data) {
//       return [true];
//     } else {
//       return [false, 'The provided input is not a valid real number.'];
//     }
//   }

//   boolValidator(data) {
//     var value = data.checked; 
//     if (typeof value === 'boolean') {
//       return [true];
//     } else {
//       return [false, 'The provided input is not a valid bool.']; 
//     }
//   }
  
//   linkValidator(data) {
//     if (data.value == '') { this.reset(); return []; }
//     try { 
//       Boolean(new URL(data.value)); 
//       return [true]; 
//     } catch (e) { 
//       return [false, 'The provided URL is invalid.']; 
//     }
//   }

//   floatValidator(data) {
//     data = data.value; 
//     if (data == '') { this.reset(); return []; }
//     if (!isNaN(parseFloat(data)) && isFinite(data)) {
//       return [true];
//     } else {
//       return [false, 'The provided input is not a valid float e.g.: 3.14.'];
//     }
//   }

//   regexValidator(data) {
//     if (data == '') { this.reset(); return []; }
//     var pattern = /(^Q\d+$)/;
//     if (pattern.test(data)) {
//       return [true];
//     } else {
//       return [false, 'Enter a valid Q-identifier: (e.g.: Q1234).'];
//     }
//   }

//   // The named event handler function
//   validateInput(event) {
//     let mainclass = this;
//     let target = event.target;

//     if (target.classList.contains('validateAs_string')) {
//       if (target.value == '') {
//         mainclass.reset(); return [];
//       } else {
//         var correct = [true]; // Strings can be empty
//       }
//     }
//     if (target.classList.contains('validateAs_wikidata')) {
//       var correct = mainclass.regexValidator(target.value);
//     }
//     if (target.classList.contains('validateAs_int')) {
//       var correct = mainclass.intValidator(target);
//     }
//     if (target.classList.contains('validateAs_bool')) {
//       var correct = mainclass.boolValidator(target);
//     }
//     if (target.classList.contains('validateAs_float')) {
//       var correct = mainclass.floatValidator(target);
//     }
//     if (target.classList.contains('validateAs_uri')) {
//       var correct = mainclass.linkValidator(target);
//     }
//     console.log(correct); 
//     if (correct.length != 0) {
//       if (target.classList.contains('validateAs_unique')) {
//         let selectedNode;
//         if (target.hasAttribute('data-nodetype_override')) {
//           selectedNode = target.getAttribute('data-nodetype_override');
//         } else {
//           selectedNode = document.getElementById('nodeTypeSelection').firstChild.value;
//         }
//         var property = target.getAttribute('data-name');
//         var unique = fetch(`/AJAX/uniqueness.php?nodetype=${selectedNode}&property=${property}&value=${target.value}`)
//           .then(response => response.json())
//           .then(data => {
//             return [data];
//           });
        
//         var correctMsg = [];
//         if (!correct[0]) {
//           correctMsg.push(correct[1]);
//         }
//         if (!unique[0]) {
//           correctMsg.push(unique[1]);
//         }
//         correctMsg = correctMsg.join(' and ');
//         correct = [correct[0] && unique[0], correctMsg];
//       }

//       var inFront = target.previousElementSibling; 
//       if (inFront && inFront.classList.contains('errorNotification')) {
//         inFront.remove(); 
//       }

//       if (correct[0]) {
//         target.classList.add('bg-green-50', 'border', 'border-green-500');
//         target.classList.remove('bg-red-50', 'border', 'border-red-500', 'validatorFlaggedMistake');
//       } else {
//         var msg = correct[1];
//         var msgHolder = document.createElement('p');
//         msgHolder.classList.add('errorNotification');
//         var msgText = document.createTextNode(msg); 
//         msgHolder.appendChild(msgText);
//         target.parentNode.insertBefore(msgHolder, target);
//         target.classList.add('bg-red-50', 'border', 'border-red-500', 'validatorFlaggedMistake');
//         target.classList.remove('bg-green-50', 'border', 'border-green-500');
//       }
//     }
//   }

//   // Adding the event listener with a named handler
//   pickup() {
//     var elements = document.getElementsByClassName('attachValidator'); 
//     for (let n = 0; n < elements.length; n++) {
//       var target = elements[n];
//       // Bind 'this' to the named handler so it can access the class context
//       target.addEventListener('change', this.validateInput.bind(this));
//     }
//   }

//   // Removing the event listener by referring to the named handler
//   removeListeners() {
//     var elements = document.getElementsByClassName('attachValidator'); 
//     for (let n = 0; n < elements.length; n++) {
//       var target = elements[n];
//       // Remove event listener by referring to the same named handler
//       target.removeEventListener('change', this.validateInput.bind(this));
//     }
//   }
// }
