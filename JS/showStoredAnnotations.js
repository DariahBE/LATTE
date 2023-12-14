/**
 *  this is to visualize whatever is stored int he DB and show it to the USER
 * 
 */


function unmark(){
  var marked = document.getElementsByClassName('markedAnnotation');
  for(var i = marked.length-1; i >= 0; i--){
    marked[i].classList.remove('markedAnnotation');
  }
}

function markBasedOnId(id){
  unmark();
  var letters = document.getElementsByClassName('ltr');
  for (var l = 0; l < letters.length; l++){
    if (letters[l].dataset.annotation  && (letters[l].dataset.annotation.split(',').includes(id)) ){
      letters[l].classList.add('markedAnnotation');
    }
  }
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
        var countOfRelatedAnnotations = origin.dataset.annotationCount;
        var relatedAnnotationIDS = origin.dataset.annotation;
        markBasedOnId(relatedAnnotationIDS);
      })
    }
  }
}
