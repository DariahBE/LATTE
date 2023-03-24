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

function findRelatedTexts(neoID){
  //do an AJAX-call and COUNT() to how many TEXT-nodes this ET is connected: 
  function isPlural(input){
    if(input != 1){return 's'}else{return ''}
  }
  fetch("/AJAX/connected_texts.php?id="+neoID)
  .then((response) => response.json())
  .then((data)=>{
    var showToUser = `Mentioned ${data.Annotations} time${isPlural(data.Annotations)} in ${data.Texts} text${isPlural(data.Texts)}`; 
    document.getElementById("relatedTextStats").innerHTML=`<p>${showToUser}</p>`;
    console.log(showToUser);
  });
}

function decideOnEdit(protected, level){
  console.log(protected, level);
  if(protected){
    return false;
  }
  else if (level > 1 ){
    return true;
  }else{
    return false;
  }
}

function showdata(data){
  //frameWorkBase();
  console.log('showdata call: ');
  console.log(data);
  toggleSlide(1);
  var annotationTarget = document.getElementById('slideoverDynamicContent');
  //superimpose the slideover on top of the nabar: 
  annotationTarget.classList.add("z-50");
  var gateWay = document.createElement('div');
  var statsTarget = document.createElement('div');
  statsTarget.setAttribute('id', 'relatedTextStats');
  statsTarget.classList.add('text-gray-600', 'w-full',  'm-5', 'p-5', 'left-0');
  gateWay.setAttribute('id', 'applicationGateway');
  annotationTarget.innerHTML = '';
  gateWay.appendChild(statsTarget);
  var authorData = data['author'];
  var annotationData = data['annotation']['properties'];
  //sends the node neoID (unstable, do not use for identifying purposes on exposed API's):
  findRelatedTexts(data['entity'][0]['neoID']); 
  var annotationStructure = data['annotationFields'];
  var annotationExtraFields = Object.keys(data['annotationFields']) || false;
  function writeField(key, data, protected, rights){
    console.log(key, data, protected, rights);
    if(!(decideOnEdit(protected, rights))){
      console.log('A');
      var field = document.createElement('p');
      var fieldkey = document.createElement('span');
      var fieldvalue = document.createElement('span');
      var keytex = annotationStructure[key] !== undefined ? annotationStructure[key][0] : key;
      var fieldkeyString = document.createTextNode(keytex);
      var fieldvalueString = document.createTextNode(data);
      fieldvalue.appendChild(fieldvalueString);
      fieldkey.appendChild(fieldkeyString);
      field.appendChild(fieldkey);
      field.appendChild(fieldvalue);
    }else{
      //if a field is write enabled. you need to type the field accordinly:
      console.log('B');
      console.log(annotationStructure[key]);
      var field = document.createElement('div');
      var fieldkey = document.createElement('p');
      var keytex = annotationStructure[key] !== undefined ? annotationStructure[key][0] : key;
      var fieldType = annotationStructure[key] !== undefined ? annotationStructure[key][1] : 'string';
      var fieldkeyString = document.createTextNode(keytex);
      fieldkey.appendChild(fieldkeyString);
      field.appendChild(fieldkey);
      var fieldvalue = document.createElement('input');
      fieldvalue.setAttribute('pattern', '^[-0-9][0-9]+$');
      var prevVal = '';
      fieldvalue.addEventListener('keyup', function(e){
        if (this.value === '-'){
          prevVal = '-';
        }
        if(this.checkValidity()){
          prevVal = this.value;
        } else {
          this.value = prevVal;
        }
      });
      //numberfields should have a live function on them to strip all non-numeric values.
    }
      if(fieldType === 'bool'){
        fieldvalue.setAttribute('type', 'boolean');
        console.log('work required in interactWithEntities.js line 108');
        //alert('Bool field should be dropdown');
      }else if(fieldType === 'uri'){
        fieldvalue.setAttribute('type', 'url');
      }else{
        fieldvalue.setAttribute('type', 'text');
      }
      fieldvalue.value = data;
      field.appendChild(fieldvalue);
      console.log('created textfield', field);    
    return field;
  }
  //work with the Annotations:
  var rightsLevel = 0;
  Object.keys(annotationData).forEach(key => {
    var row = annotationData[key];
    var rowkey = row[0];
    var rowdata = row[1];
    var protected = row[2];
    rightsLevel = row[3];
    if (annotationExtraFields){
      var idx = annotationExtraFields.indexOf(rowkey);  //-1 if not exists.
      if(idx>=0){
        annotationExtraFields.splice(idx,1);
      }
    }
    var fieldFormatted = writeField(rowkey, rowdata, protected, rightsLevel);
    annotationTarget.appendChild(fieldFormatted);
  });
  //for all annotationExtraFields create a new editable field:
  for (var i = 0; i < annotationExtraFields.length; i++){
    annotationTarget.appendChild(writeField(annotationExtraFields[i], '', false, rightsLevel));
  }
  //work with the Author of the annotation:
  Object.keys(authorData).forEach(key => {
    var row = authorData[key];
    var rowkey = row[0];
    var rowdata = row[1];
    var protected = row[2];
  });
  //show the type of the annotation as an enlarged entry: 
  var etType = document.createElement('h3'); 
  var etTypeText = document.createTextNode(data['entity'][0]['type']); 
  etType.appendChild(etTypeText);
  annotationTarget.appendChild(etType);
  var linkToGraphExplorer = '/explore/'+data['entity'][0]['neoID']; 
  var linkElement = document.createElement('a'); 
  linkElement.setAttribute('href', linkToGraphExplorer); 
  linkElement.setAttribute('target', '_blank');
  imgElement = document.createElement('img');
  imgElement.src = '/images/graphExplore.png';
  linkElement.appendChild(imgElement);
  gateWay.appendChild(linkElement);
  annotationTarget.appendChild(gateWay);

  //With the type known: look up if there's a wikidata attribute: 
  var qidArr = data['entity'][0]['properties'].filter(ar => ar[2]== 'wikidata');
  if (qidArr.length === 1){
    var qid = qidArr[0][1];
    //console.log(qid);
    var wd = new wikibaseEntry(qid, wdProperties, 'qid');
    wd.getWikidata()
      .then(function(){wd.renderEntities(qid)}); 
      //console.log(x);
  }
}

function handleError(){
  alert('handleError function needs to be rewritten for a more uniform layout. '); 
  return;
  //frameWorkBase();
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
  console.log('Normal Entry!');
  var eventsource = event.source || event.target;
  //event.preventDefault();
  //console.log(eventsource);
  var annotationID = eventsource.dataset.annotation;
  getInfoFromBackend("/AJAX/resolve_annotation.php?annotation="+annotationID)
    .then((data)=>{
      console.log('interactWithEntities.js: rewrite loadAnnotationData, handeError & showdata functions'); 
      showdata(data);
    })
    //.catch(err => handleError() );
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
