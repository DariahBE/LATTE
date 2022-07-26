function visualizeStoredAnnotations(){
  console.log(storedAnnotations);
  var positions = {};
  for (const [key, properties] of Object.entries(storedAnnotations['relations'])) {
    var starts = properties['start'];
    var stops = properties['stop'];
    //var owner = properties['creator'];
    console.log(key, starts, stops);
    for(var i = starts; i <= stops; i++){
      positions[i.toString()].push(key);
    }
  }
  console.log(positions);
}
