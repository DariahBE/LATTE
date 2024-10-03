/**
 *  this is to visualize whatever is stored int he DB and show it to the USER
 *  when overlapping annotations are detected, these are shown in a modal which 
 *  allows the user to further refine the annotation they want to laod into the DOM
 */


function unmark() {
  const marked = document.querySelectorAll('.markedAnnotation');
  marked.forEach(element => {
      element.classList.remove('markedAnnotation');
  });
}

function markBasedOnId(id){
  unmark();
  var letters = document.getElementsByClassName('ltr');
  let highlightedText = ''; 
  for (var l = 0; l < letters.length; l++){
    if (letters[l].dataset.annotation  && (letters[l].dataset.annotation.split(',').includes(id)) ){
      letters[l].classList.add('markedAnnotation');
      highlightedText += letters[l].textContent;
    }
  }
  globalSelectionText = highlightedText;
}

function textByUUID(uuid){
  /**
   * Takes the UUID from an annotation and returns the text that's contained by it.
   */
  var letters = document.getElementsByClassName('ltr');
  let text = '';
  for (var l = 0; l < letters.length; l++){
    if (letters[l].dataset.annotation && (letters[l].dataset.annotation.split(',').includes(uuid))){
      text += letters[l].textContent;
    } else{
      //early return of text if text is not empty (has data in it) and the letter you parsed
      // does not belong to the given uuid any more.
      if(text != ''){
        return text;
      }
    }
  }
  return text;
}

function makeMultiBox(ids){
  /** When multiple entities have to be shown, creates a special modal box
   * that will load the different uuids for the user to see and choose from.
   */
  //fetch the base box to diplay multi IDs: 
  var [div, mode, topDst, height, leftDst] = makeBoxTemplate(); 
  div.setAttribute('id', 'multibox'); 
  let multiElementDiv = document.createElement('div'); 
  // multiElementDiv.classList.add('')
  (ids).forEach(element => {
    let displayText = textByUUID(element); 
    let textElement = document.createElement('p');
    textElement.classList.add('textElementMulti');
    textElement.dataset.for_annotation = element; 
    textElement.textContent = displayText;
    //add color code for entity type to annotation in multibox.
    let elmClass = storedAnnotations['relations'][element]['type']; 
    textElement.classList.add(elmClass);
    textElement.onclick = () => {
      let elm = event.srcElement || event.target;
      let uuid = elm.dataset.for_annotation;
      //alert(elm.dataset.for_annotation);
      loadAnnotationData(uuid);
  };
    multiElementDiv.appendChild(textElement); 
  });
  div.appendChild(multiElementDiv); 
  div.classList.add('suggestionBox', 'bg-white');
  div.style.position = 'absolute';
  div.style.top = topDst + height + 'px';
  div.style.left = leftDst + 'px';
  div.style.minWidth = '250px';
  div.style.maxWidth = '300px';
  div.style.minHeight = '100px';
  div.style.maxHeight = '200px';
  let closeButton = document.createElement('button'); 
  let closeButtonText = document.createTextNode('Close'); 
  closeButton.appendChild(closeButtonText);
  closeButton.classList.add('red-bg-500'); 
  closeButton.onclick = () => {
    ignoreSuggestion("multibox");
  }
  div.appendChild(closeButton)
  //add all content to the DOM
  document.body.appendChild(div);
}


function visualizeStoredAnnotations(){
  var positions = {};
  for (const [key, properties] of Object.entries(storedAnnotations['relations'])) {
    var starts = properties['start'];
    var stops = properties['stop'];
    var type = properties['type'];
    for(var i = starts; i <= stops; i++){
      var j = i.toString();
      if(!(j in positions)){
        positions[j] = [[key],[type]];
      }else{
        positions[j][0].push(key);
        positions[j][1].push(type);
      }
    }
  }

  for (const auto_anno of automatic_annotations){
    var starts = auto_anno['start']; 
    var stops = auto_anno['stop']; 
    var key = auto_anno['annotation']; 
    for(var i = starts; i <= stops; i++){
      var j = i.toString();
      if(!(j in positions)){
        positions[j] = [[key],['app_automatic']];
      }else{
        positions[j][0].push(key);
        positions[j][1].push(type);
      }
    }
  }
  //Mark the positions where the index matches the data-attribute.
  //Add the annotation UUID to a new data-attribute.
  //cases: 0, 1, > 1;
  var letters = document.getElementsByClassName('ltr');
  for(var l = 0; l < letters.length; l++){
    var ltr = letters[l];
    if(ltr.dataset.itercounter && ltr.dataset.itercounter in positions){
      ltr.classList.add('linked', 'underline');
      ltr.dataset.annotation = positions[ltr.dataset.itercounter][0].toString();
      ltr.dataset.annotationCount = positions[ltr.dataset.itercounter][0].length;
      ltr.classList.add(positions[ltr.dataset.itercounter][1])
      ltr.addEventListener('click', function(){
        console.log('letterBasedEntry');
        var origin = event.source || event.target;
        var relatedAnnotationIDS = origin.dataset.annotation;
        var relatedAnnotations = relatedAnnotationIDS.split(',');
        console.log(relatedAnnotations);
        console.log(relatedAnnotations[0]);
        markBasedOnId(relatedAnnotations[0]);
        if(relatedAnnotations.length > 1){
          makeMultiBox(relatedAnnotations); 
        }
      })
    }
  }
}
