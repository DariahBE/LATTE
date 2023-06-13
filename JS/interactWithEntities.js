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
/*
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
}*/

function showdata(data){
  //frameWorkBase();
  toggleSlide(1);
  var annotationTarget = document.getElementById('slideoverDynamicContent');
  //superimpose the slideover on top of the nabar: 
  annotationTarget.classList.add("z-50");
  var gateWay = document.createElement('div');
  var statsTarget = document.createElement('div');
  statsTarget.setAttribute('id', 'relatedTextStats');
  statsTarget.classList.add('text-gray-600', 'w-full',  'm-2', 'p-2', 'left-0');
  annotationTarget.innerHTML = '';
  gateWay.appendChild(statsTarget);
  var authorData = data['author'];
  var annotationData = data['annotation']['properties'];
  //sends the node neoID (unstable, do not use for identifying purposes on exposed API's):
  findRelatedTexts(data['entity'][0]['neoID']); 
  var annotationStructure = data['annotationFields'];
  var annotationExtraFields = Object.keys(data['annotationFields']) || false;
  function writeField(key, data, protected){
    console.log(key, data, protected);
    //if(!(decideOnEdit(protected, rights))){
      console.log('A');
      var field = document.createElement('p');
      var fieldkey = document.createElement('span');
      fieldkey.classList.add('labelKey', 'font-bold');
      var fieldvalue = document.createElement('span');
      var fieldType = annotationStructure[key] !== undefined ? annotationStructure[key][1] : 'string';
      var keytex = annotationStructure[key] !== undefined ? annotationStructure[key][0] : key;
      var fieldkeyString = document.createTextNode(keytex+': ');
      var fieldvalueString = document.createTextNode(data);
      console.log(fieldvalueString); 
      if (data !== false && data!==''){
        fieldvalue.appendChild(fieldvalueString);
        fieldkey.appendChild(fieldkeyString);
        field.appendChild(fieldkey);
        field.appendChild(fieldvalue);
      }

    //}else{
      /*  alert( 'B Block');
      
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
      });*/
      //numberfields should have a live function on them to strip all non-numeric values.
   // }
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
  Object.keys(authorData).forEach(key =>{
    var row = authorData[key];
    var rowkey = row[0];
    var rowdata = row[1];
    var protected = row[2];
  });
  //show the type of the annotation as an enlarged entry: 
  var etType = document.createElement('h3'); 
  var etStable = data['entity'][0]['stableURI']; 
  etType.classList.add('font-bold', 'text-lg', 'w-full', 'items-center', 'flex',  'justify-center'); 
  var etTypeText = document.createTextNode('Entity: '+data['entity'][0]['type']); 
  etType.appendChild(etTypeText);
  annotationTarget.appendChild(etType);
  var linkToGraphExplorer = '/explore/'+data['entity'][0]['neoID']; 
  var linkElement = document.createElement('a'); 
  linkElement.setAttribute('href', linkToGraphExplorer); 
  linkElement.setAttribute('target', '_blank');
  imgElement = document.createElement('img');
  imgElement.src = '/images/graphExplore.png';
  linkElement.appendChild(imgElement);
  var linkToStablePage = document.createElement('a'); 
  linkToStablePage.setAttribute('href', etStable); 
  linkToStablePage.setAttribute('target', '_blank');
  stableImgElement = document.createElement('i'); 
  stableImgElement.classList.add('fas', 'fa-anchor'); 
  //stableImgElement.innerHTML = '<i class="fa-anchor"></i>'; 
  linkToStablePage.appendChild(stableImgElement); 
  subdivGateway = document.createElement('div'); 
  subdivGateway.classList.add('flex', 'flex-row'); 
  subdivGateway.appendChild(linkElement);
  subdivGateway.appendChild(linkToStablePage);
  gateWay.appendChild(subdivGateway); 
  annotationTarget.appendChild(gateWay);

  //With the type known: look up if there's a wikidata attribute: 
  var qidArr = data['entity'][0]['properties'].filter(ar => ar[2]== 'wikidata');
  if (qidArr.length === 1){
    var qid = qidArr[0][1];
    var wd = new wikibaseEntry(qid, wdProperties, 'slideover', 'qid');
    wd.getWikidata()
      .then(function(){wd.renderEntities(qid)}); 
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
  var annotationID = eventsource.dataset.annotation;
  getInfoFromBackend("/AJAX/resolve_annotation.php?annotation="+annotationID)
    .then((data)=>{
      console.log('interactWithEntities.js: rewrite loadAnnotationData, handeError & showdata functions'); 
      console.warn('showdata data:'); 
      console.log(data); 
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


//global scope
let spellingVariantTracker = [];
function binVariant(e){
  //gets the attribute of e: sends XHR request to delete. 
  const DOMElement = e.parentElement; 
  let nodeInternalId = e.getAttribute('neoid'); 
  let etInternalId = document.getElementById('connectSuggestion').getAttribute('neoid'); 
  fetch('/AJAX/variants/delete.php?variantid='+nodeInternalId+'&entityid='+etInternalId)
  .then(data => function(){
    console.log(data);
  });

  console.log(nodeInternalId); 
  console.log(DOMElement); 
  const writtenValue = DOMElement.textContent; 
  //then removes it from the spellingvarianttracker
  let idx = spellingVariantTracker.indexOf(writtenValue); 
  delete(spellingVariantTracker[idx]); 
  //tehn removes it from the DOM: 
  e.parentElement.remove();
}

function displayWrittenVariants(variantData){
      //    allow the user to generate a list of spelling variants: 
      var spellingVariantMainBox = document.createElement('div');
      spellingVariantMainBox.setAttribute('id', 'embeddedSpellingVariants');
      var spellingVariantTitle = document.createElement('h3'); 
      spellingVariantTitle.appendChild(document.createTextNode('Naming variants: '));
      spellingVariantMainBox.appendChild(spellingVariantTitle);
      spellingVariantMainBox.classList.add('border-solid', 'border-2', 'border-black-800', 'rounded-md', 'flex-grow'); 
      var spellingVariantCreation = document.createElement('input'); 
      spellingVariantCreation.setAttribute('id', 'variantInputBox'); 
      spellingVariantCreation.classList.add('border-solid', 'border-2')
      var spellingVariantSubBox = document.createElement('div');
      spellingVariantSubBox.setAttribute('id', 'variantStorageBox'); 
      spellingVariantSubBox.classList.add('flex', 'border-t-2', 'border-t-dashed', 'flex', 'flex-wrap');
      var addToStorageBox = document.createElement('button'); 
      addToStorageBox.appendChild(document.createTextNode('Add')); 
      addToStorageBox.addEventListener('click', function(){
        var writtenValue = document.getElementById('variantInputBox').value; 
        document.getElementById('variantInputBox').value = ''; 
        if(spellingVariantTracker.includes(writtenValue)){
          return;
        }
        spellingVariantTracker.push(writtenValue);
        var storeIn = document.getElementById('variantStorageBox'); 
        var variantDisplayDiv = document.createElement('div'); 
        variantDisplayDiv.classList.add('m-1','p-1','spellingvariantbox', 'bg-amber-100', 'flex');
        var variantDisplayTex = document.createElement('p');
        variantDisplayTex.appendChild(document.createTextNode(writtenValue));
        var variantDisplayBin = document.createElement('p');
        variantDisplayBin.classList.add('xsbinicon', 'bg-amber-200', 'm-1','p-1', 'rounded-full'); 
        variantDisplayBin.addEventListener('click', function(){binVariant(this);});
        variantDisplayDiv.appendChild(variantDisplayTex);
        variantDisplayDiv.appendChild(variantDisplayBin);
        storeIn.appendChild(variantDisplayDiv);
      }); 
      spellingVariantMainBox.appendChild(spellingVariantCreation);
      spellingVariantMainBox.appendChild(addToStorageBox);
      spellingVariantMainBox.appendChild(spellingVariantSubBox);
  
  //used by showdata() and showDBInfoFor() functions. 
  let varTarget = document.getElementById('variantStorageBox');
  //console.log(varTarget); 
  for(let i = 0; i < variantData.length; i++){
    let variant = variantData[i]; 
    let varbox = document.createElement('div'); 
    varbox.classList.add('m-1','p-1', 'spellingvariantbox', 'bg-amber-100', 'flex');
    let varboxDelete = document.createElement('p'); 
    let varboxContent = document.createElement('p'); 
    varboxContent.appendChild(document.createTextNode(variant['label'])); 
    varboxDelete.setAttribute('data-id', variant['primary'][1]); 
    varboxDelete.setAttribute('data-key', variant['primary'][0]); 
    varboxDelete.setAttribute('data-neoid', variant['neoid']); 
    varboxDelete.classList.add('xsbinicon', 'bg-amber-200', 'm-1', 'p-1', 'rounded-full');
    varboxDelete.addEventListener('click', function(){binVariant(this);});
    varbox.appendChild(varboxContent);
    varbox.appendChild(varboxDelete);
    varTarget.appendChild(varbox);
  }

}

function showDBInfoFor(id){
  //gets the neoID of an entity node after having found a matching Q-id.
  //sends it to the BE
  // shows all data there's stored about it.
  console.warn('fetching database info');
  getInfoFromBackend('/AJAX/getETById.php?id='+id)
  .then((data)=> {
    //process entity information
    const info = data['props']; 
    for(let i = 0; i < info.length; i++){
      let infoBlock = info[i];
    }
    //process: variants
    const variants = data['variantSpellings']; 
    displayWrittenVariants(variants); 
    console.log(info, variants); 


  }); 

}

$(document).ready(function(){
  addInteractionToEntities();
});
