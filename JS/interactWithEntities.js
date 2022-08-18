function getInfoFromBackend(url){
  var myPromise = new Promise ((resolve, reject)=>{
    $.ajax({
      type:"GET",
      headers: {"Content-type": "application/x-www-form-urlencoded; charset=UTF-8"},
      url:url,
      success: function(result){
        resolve(result);
      }
    });

  })
  return myPromise;
}

function frameWorkBase(){
  var insertHere = document.getElementById('rightExtensionPanel');
  insertHere.innerHTML = '';
  //main container:
  var maindiv = document.createElement('div');
  maindiv.setAttribute('id', 'maindiv');
  maindiv.classList.add('container');
  //add title to maincontainer:
  var title = document.createElement('h3');
  title.setAttribute('id', 'annotationTitle');
  title.classList.add('font-bold','flex', 'items-center', 'justify-center');
  var titleTex = document.createTextNode('Annotation: ');
  title.appendChild(titleTex);
  maindiv.appendChild(title)
  //under title add a DIV with variable metadata on Annotation Node:
  var annotationDiv = document.createElement('div');
  annotationDiv.setAttribute('id', 'annotationContainerAjax');
  annotationDiv.classList.add('font-small');
  maindiv.appendChild(annotationDiv);
  //add a DIV with author information:
  var authorDiv = document.createElement('div');
  authorDiv.setAttribute('id', 'authorContainerAjax');
  authorDiv.classList.add('font-small');
  maindiv.appendChild(authorDiv);
  //add a DIV with variants:
  var variantDiv = document.createElement('div');
  variantDiv.setAttribute('id', 'variantContainerAjax');
  variantDiv.classList.add('font-small');
  maindiv.appendChild(variantDiv);
  insertHere.appendChild(maindiv);
}

function showdata(data){
  frameWorkBase();
  var annotationTarget = document.getElementById('annotationContainerAjax');
  var authorData = data['author'];
  var annotationData = data['annotation']['properties'];
  var annotationExtraFields = data['annotationFields'] || false;
  function writeField(key, data, protected){
    console.log(key, data, protected);
    if(protected){
      console.log('A');
      var field = document.createElement('p');
      var fieldkey = document.createElement('span');
      var fieldvalue = document.createElement('span');
      var fieldkeyString = document.createTextNode(key);
      var fieldvalueString = document.createTextNode(data);
      fieldvalue.appendChild(fieldvalueString);
      fieldkey.appendChild(fieldkeyString);
      field.appendChild(fieldkey);
      field.appendChild(fieldvalue);
    }else{
      console.log('B');
      var field = document.createElement('div');
      var fieldkey = document.createElement('p');
      var fieldkeyString = document.createTextNode(key);
      fieldkey.appendChild(fieldkeyString);
      field.appendChild(fieldkey);
      var fieldvalue = document.createElement('input');
      fieldvalue.setAttribute('type', 'text');
      fieldvalue.value = data;
      field.appendChild(fieldvalue);
      console.log('created textfield', field);
    }
    return field;
  }
  //work with the Annotations:
  Object.keys(annotationData).forEach(key => {
    var row = annotationData[key];
    var rowkey = row[0];
    var rowdata = row[1];
    var protected = row[2];
    if (annotationExtraFields){
      var idx = annotationExtraFields.indexOf(rowkey);  //-1 if not exists.
      if(idx>=0){
        annotationExtraFields.splice(idx,1);
      }
    }
    var fieldFormatted = writeField(rowkey, rowdata, protected);
    annotationTarget.appendChild(fieldFormatted);
  });
  //for all annotationExtraFields create a new editable field:
  for (var i = 0; i < annotationExtraFields.length; i++){
    annotationTarget.appendChild(writeField(annotationExtraFields[i], '', false));
  }
  //work with the Author of the annotation:
  Object.keys(authorData).forEach(key => {
    var row = authorData[key];
    var rowkey = row[0];
    var rowdata = row[1];
    var protected = row[2];

  });
}

function handleError(){
  frameWorkBase();
  var target = document.getElementById('annotationContainerAjax');
  target.classList.add('bg-red-100', 'rounded-lg', 'py-5', 'px-6', 'mb-3', 'text-base', 'text-red-700', 'inline-flex', 'items-center');
  var errtitle = document.createElement('h4');
  var errdiv = document.createElement('div');
  errdiv.classList.add('flex', 'items-center', 'justify-center');
  var errsvg = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /> </svg>';
  errdiv.innerHTML = errsvg;
  errtitle.classList.add('warning');
  var errmessage = document.createElement('p');
  errmessage.classList.add('warning');
  var errmessagetext = document.createTextNode('An unspecified error occurred. The ID may not be valid or has been deleted from the database.');
  errmessage.appendChild(errmessagetext);
  target.appendChild(errtitle);
  target.appendChild(errdiv);
  target.appendChild(errmessage);
}

function loadAnnotationData(){
  var eventsource = event.source || event.target;
  //console.log(eventsource);
  var annotationID = eventsource.dataset.annotation;
  getInfoFromBackend("/AJAX/resolve_annotation.php?annotation="+annotationID)
    .then((data)=>{
      showdata(data);
    })
    .catch(err => handleError() );
}

function addInteractionToEntities(){
  var links = document.getElementsByClassName('linked');
  for(var i = 0; i < links.length; i++){
    links[i].addEventListener('click', function(){
      loadAnnotationData();
    })
  }
};

//console.log('present');
$(document).ready(function(){
  addInteractionToEntities();
});
