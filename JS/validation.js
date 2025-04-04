
class Validator{
  //autofetching is allowed by default
  use_autofetch = true;
  etnode_dd_id;

  set_id_of_dropdown_element_for_entity(element){
    this.etnode_dd_id = element; 
  }


  set_autofetch_to(to_bool){
    /*method to override the use of autofetching */
    this.use_autofetch = to_bool;
  }

  get_autofetch_as_bool(){
    /*method to get the state of autofetching(allowed or not) */
    return this.use_autofetch; 
  }

  checkRequired(){
    //required is set to all elements which have the unique attribute with the exception 
    // of wikidata elements - these are allowed to be empty or assigned to multiple 
    // nodes.
    /**
     * Called before sumbit event: you need this because the change event
     * is never triggered when a user skips over required fields!.
     */
    let unique_elements = document.getElementsByClassName('validateAs_unique'); 
    for (let i = 0; i < unique_elements.length; i++) {
      let elm = unique_elements[i];
      if (elm.classList.contains('validateAs_wikidata')) {
        continue;
      }
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
    // .value will return an empty string for 123A.
    // .valueAsNumber returns NaN, but also for empty inputs.
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

  async autofetcher(for_item){
    //get the node type
    let node = null; 
    if(this.etnode_dd_id){
      node = document.getElementById(this.etnode_dd_id).value; 
    }
    //check if node type has an override set to it
    let overridenode = for_item.getAttribute('data-nodetype_override');
    //get the name of the field attribute.
    let attribute_name = for_item.getAttribute('data-name'); 
    let nodename;
    if (overridenode){
      nodename = overridenode; 
    }else{
      nodename = node;
    }
    //send it using XHR to get next value in line. 
    await fetch("/AJAX/getNext.php?node="+nodename+"&prop="+attribute_name)
    .then((response) => response.json())
    .then((data) =>{
        //set the value of the field to the next value in line.
        for_item.value = data[0];
        // force a new change event
        var event = new Event('change', {
          'bubbles': true,
          'cancelable': true
        });
        for_item.dispatchEvent(event);

      }
    )
  }

  attach_autofetch(item){
    if(this.get_autofetch_as_bool()){
      var mainclass = this;
      let fetch_button = document.createElement('button'); 
      fetch_button.textContent = 'fill';
      fetch_button.onclick = function(){
        mainclass.autofetcher(item);
      }
      item.parentElement.appendChild(fetch_button);
    }
  }

  pickup(){
    var elements = document.getElementsByClassName('attachValidator'); 
    var mainclass = this;
    for(var n=0; n<elements.length; n++){
      var target = elements[n];
      if (target.classList.contains('monitored')){
        continue;
      }
      if(target.classList.contains('validateAs_int') && target.classList.contains('validateAs_unique') && this.use_autofetch){
        mainclass.attach_autofetch(target);
      }
      target.classList.add('monitored');
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
