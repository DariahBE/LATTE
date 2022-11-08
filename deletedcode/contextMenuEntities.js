console.log('contextMenuEnitites still required? ');

function closeEntityOnSelectDiv(){
  var boxes = document.getElementsByClassName('entityBySelectBox');
  for (var i = 0; i < boxes.length; i++ ){
    var elem = boxes[i]
    elem.parentNode.removeChild(elem);
  }
}

function buildSelectController(){
  if (window.getSelection) {
    var selectedString=  window.getSelection().toString();
  } else if (document.selection) {
    var selectedString=  document.selection.createRange().text;
  }
  if(selectedString.length > 0){
    console.log(selectedString);
    var source = event.target || event.srcElement;
    var xCoord = event.pageX;
    var yCoord = event.pageY;

  }
  // create a custom box near the cursor:
  //      LOOKUP ==> need to set subslice place/text
  //      Create
  //

}


function attachSelectController(){
  alert('still required in attachSelectController()');
  $("#textcontent").on('mouseup', function(){
    buildSelectController();
  })
}
