function unmark(){
  var marked = document.getElementsByClassName('markedAnnotation');
  for(var i = 0; i < marked.length; i++){
    marked[i].classList.remove('markedAnnotation');
  }
}

function markBasedOnId(id){
  unmark();
  console.log(id);
  letters = document.getElementsByClassName('ltr');
  for (var l = 0; l < letters.length; l++){
    if (letters[l].dataset.annotation){
      console.log(letters[l].dataset.annotation.split(',')[0]);
      console.log(id, id in letters[l].dataset.annotation.split(','))
    }
    if (letters[l].dataset.annotation  && (id in letters[l].dataset.annotation.split(',')) ){
      letters[l].classList.add('markedAnnotation');
    }
  }


}


function visualizeStoredAnnotations(){
  var positions = {};
  for (const [key, properties] of Object.entries(storedAnnotations['relations'])) {
    var starts = properties['start'];
    var stops = properties['stop'];
    var type = properties['type']
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
  console.log(positions);

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
        var origin = event.source || event.target;
        var countOfRelatedAnnotations = origin.dataset.annotationCount;
        var relatedAnnotationIDS = origin.dataset.annotation;
        console.log(relatedAnnotationIDS);
        markBasedOnId(relatedAnnotationIDS);
      })
    }
  }
}
