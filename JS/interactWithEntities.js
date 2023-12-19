let datamode = null; 

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
    //console.log(showToUser);
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

function waitForElement(selector) {
  return new Promise(resolve => {
      if (document.querySelector(selector)) {
          return resolve(document.querySelector(selector));
      }

      const observer = new MutationObserver(mutations => {
          if (document.querySelector(selector)) {
              resolve(document.querySelector(selector));
              observer.disconnect();
          }
      });

      observer.observe(document.body, {
          childList: true,
          subtree: true
      });
  });
}


function createStableLinkingBlock(nodeid, stableURI){
  //generates a link to the graph explorer page
  //and to the stable URI 
  //returns a DIV with both link elements embedded in.
  var linkToGraphExplorer = '/explore/'+nodeid; 
  var linkElement = document.createElement('a'); 
  linkElement.setAttribute('href', linkToGraphExplorer); 
  linkElement.setAttribute('target', '_blank');
  imgElement = document.createElement('img');
  imgElement.src = '/images/graphExplore.png';
  linkElement.appendChild(imgElement);
  var linkToStablePage = document.createElement('a'); 
  linkToStablePage.setAttribute('href', stableURI); 
  linkToStablePage.setAttribute('target', '_blank');
  stableImgElement = document.createElement('i'); 
  stableImgElement.classList.add('fas', 'fa-anchor'); 
  linkToStablePage.appendChild(stableImgElement); 
  subdivGateway = document.createElement('div'); 
  subdivGateway.classList.add('flex', 'flex-row'); 
  subdivGateway.setAttribute('id', 'stableBox'); 
  subdivGateway.appendChild(linkElement);
  subdivGateway.appendChild(linkToStablePage);
  return subdivGateway;
}


function showdata(data){
  //set the global datamode: 
  datamode = data['mode'];
  //frameWorkBase();
  toggleSlide(1);
  createSideSkelleton(); 
  var annotationTarget = document.getElementById('slideoverDynamicContent');
  //superimpose the slideover on top of the nabar: 
  annotationTarget.classList.add("z-50");
  var gateWay = document.createElement('div');
  gateWay.setAttribute('id', 'neobox');
  var statsTarget = document.createElement('div');
  statsTarget.setAttribute('id', 'relatedTextStats');
  statsTarget.classList.add('text-gray-600', 'w-full',  'm-2', 'p-2', 'left-0');
  annotationTarget.innerHTML = '';
  gateWay.appendChild(statsTarget);
  var variantsTarget = document.createElement('div'); 
  variantsTarget.setAttribute('id', 'etVariantsTarget')
  variantsTarget.classList.add('text-gray-600', 'w-full',  'm-2', 'p-2', 'left-0');
  gateWay.appendChild(variantsTarget); 
  var authorData = data['author'];
  var annotationData = data['annotation']['properties'];
  //sends the node neoID (unstable, do not use for identifying purposes on exposed API's):
  if(datamode === 'controll'){
    findRelatedTexts(data['entity'][0]['neoID']); 
  }
  var annotationStructure = data['annotationFields'];
  var annotationExtraFields = Object.keys(data['annotationFields']) || false;
  function writeField(key, data, protected){
    console.log('Method A', key, data, protected);
    //if(!(decideOnEdit(protected, rights))){
      var field = document.createElement('p');
      var fieldkey = document.createElement('span');
      fieldkey.classList.add('labelKey', 'font-bold');
      var fieldType = annotationStructure[key] !== undefined ? annotationStructure[key][1] : 'string';
      var keytex = annotationStructure[key] !== undefined ? annotationStructure[key][0] : key;
      var fieldkeyString = document.createTextNode(keytex+': ');
      if (data !== false && data!==''){
        fieldkey.appendChild(fieldkeyString);
        field.appendChild(fieldkey);
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
        var fieldvalue = document.createElement('span');
        var fieldvalueString = document.createTextNode(data);
        fieldvalue.appendChild(fieldvalueString);
        field.appendChild(fieldvalue);
  
        fieldvalue.setAttribute('type', 'boolean');
        console.log('work required in interactWithEntities.js line 108');
        //alert('Bool field should be dropdown');
      }else if(fieldType === 'uri'){
        var fieldvalue = document.createElement('a');
        fieldvalue.setAttribute('href', data); 
        fieldvalue.setAttribute('target', '_blank'); 
        fieldvalue.appendChild(document.createTextNode(data)); 
        //var fieldvalueString = document.createTextNode(data);
        //fieldvalue.appendChild(fieldvalueString);
        field.appendChild(fieldvalue);
        fieldvalue.setAttribute('type', 'url');
        //let clickhref = document.createElement('a'); 
        //clickhref.setAttribute('href', data); 
        //clickhref.setAttribute('target', '_blank'); 
        //clickhref.appendChild(document.createTextNode(data)); 
        //fieldvalue.appendChild(clickhref); 
      }else{
        var fieldvalue = document.createElement('span');
        var fieldvalueString = document.createTextNode(data);
        fieldvalue.appendChild(fieldvalueString);
        field.appendChild(fieldvalue);  
        fieldvalue.setAttribute('type', 'text');
        fieldvalue.value = data;
        field.appendChild(fieldvalue);  
      }
      console.log('created textfield', field);    
    return field;
  }
  //work with the Annotations:
  console.log(annotationData);
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
  /*
  // TODO determine if this codeblock has to go back into production. 
  //work with the Author of the annotation:
  Object.keys(authorData).forEach(key =>{
    var row = authorData[key];
    var rowkey = row[0];
    var rowdata = row[1];
    var protected = row[2];
  });*/
  //show the type of the annotation as a header entry: 
  if(datamode === 'controll'){
    var etType = document.createElement('h3'); 
    var etStable = data['entity'][0]['stableURI']; 
    etType.classList.add('font-bold', 'text-lg', 'w-full', 'items-center', 'flex',  'justify-center'); 
    var etTypeText = document.createTextNode('Entity: '+data['entity'][0]['type']); 
    etType.appendChild(etTypeText);
    annotationTarget.appendChild(etType); 
    let neoid = data['entity'][0]['neoID']; 
    gateWay.appendChild(createStableLinkingBlock(neoid, etStable)); 
    annotationTarget.appendChild(gateWay);
    //display the variant data: 
    displayET_variant(data['variants'], neoid); 
    //With the type known: look up if there's a wikidata attribute: 
    var qidArr = data['entity'][0]['properties'].filter(ar => ar[2]== 'wikidata');
    if (qidArr.length === 1){
      var qid = qidArr[0][1];
      var wd = new wikibaseEntry(qid, wdProperties, 'slideover', 'qid');
      wd.getWikidata()
        .then(function(){wd.renderEntities(qid)}); 
    }
  } else if (datamode === 'automated'){
    console.log('start here.'); 
    //find the selected text: 
    let highlighted = document.getElementsByClassName("markedAnnotation"); 
    let highlightedText = ''; 
    console.log(highlighted.innerHTML); 
    for (const element of highlighted) {
      highlightedText+=element.textContent;
    }
    console.log(highlightedText); 
    //BUG: critical find start and stop position for loadIntoSuggestionBox. 
    //loadAnnotationData(); 
    //send selected text + start + stop to backend!
    //loadIntoSuggestionBox(highlightedText, 17, 80);
    
    $baseURL = '/AJAX/getEntitySuggestion.php?';
    $parameters = {
      'type':'',    //type is empty as there was no pickup by NERtool
      'value':highlightedText,
      'casesensitive':false
    };
    $sendTo = $baseURL+jQuery.param($parameters);
    makeSuggestionBox();
    getInfoFromBackend($sendTo)
    .then((data)=>{
      loadIntoSuggestionBox(data, 17, 50);
    })
    // TODO: CRITICAL
    /**
     *        1) code needs to perform a lookup in the DOM and see what the annotated text is. 
     *        2) This annotated text should be treated as if you select a part of the DOM text and look it up in the DB. 
     *        3) UPON approval= 
     *            - update annotation Label from annoation_auto to annoation
     *            - add other fields in DOM to approve edit and let the user annotate properly. 
     * 
     */
    //alert('todo'); 
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
      /*console.log('interactWithEntities.js: rewrite loadAnnotationData, handeError & showdata functions'); 
      console.warn('showdata data:'); 
      console.log(data); */
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

/*
//global scope
//let spellingVariantTracker = [];
function binVariant(e){
  // Part of BUG 9 patch: 
  // code moved to et_variants.js
  //gets the attribute of e: sends XHR request to delete. 
  console.log(e); 
  const DOMElement = e.parentElement; 
  let nodeInternalId = e.getAttribute('data-neoid'); 
  let etInternalId = document.getElementById('connectSuggestion').getAttribute('data-neoid'); 
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
*/

// debug process of BUG9 ==> code disabled 
// should be moved to /JS/et_variants.js
function displayWrittenVariants(variantData){
  alert('Outdated call to interactWithEntities.js > displayWrittenVariants(arg:variantData)');
  /*
      //    allow the user to generate a list of spelling variants: 
      let varbox = document.getElementById('embeddedSpellingVariants');
      // TODO
      //alert('this still has to be done');
      if(varbox !== null){
        varbox.parentNode.removeChild(varbox);
      }
      var spellingVariantMainBox = document.createElement('div');
      spellingVariantMainBox.setAttribute('id', 'embeddedSpellingVariants');
      var spellingVariantTitle = document.createElement('h3'); 
      spellingVariantTitle.appendChild(document.createTextNode('Naming variants: '));
      spellingVariantMainBox.appendChild(spellingVariantTitle);
      spellingVariantMainBox.classList.add('border-solid', 'border-2', 'border-black-800', 'rounded-md', 'flex-grow'); 
      var spellingVariantCreation = document.createElement('input'); 
      spellingVariantCreation.setAttribute('id', 'variantInputBox'); 
      spellingVariantCreation.classList.add('border-solid', 'border-2');
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
  console.warn('variantStorageBox missing in DOM'); 
  console.log(varTarget); 
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
*/
}

function showDBInfoFor(id, extra=''){
  //gets the neoID of an entity node after having found a matching Q-id.
  //sends it to the BE
  let extended = ''; 
  if(extra){
    extended = '&extended=1'
  }
  // shows all data there's stored about it.
  console.warn('fetching database info');
  getInfoFromBackend('/AJAX/getETById.php?id='+id+extended)
  .then((data)=> {
    //process entity information
    const info = data['props']; 
    for(let i = 0; i < info.length; i++){
      let infoBlock = info[i];
      let blockName = infoBlock[0]; 
      let blockData = infoBlock[1];
      console.log(infoBlock); 
    }
    //process: variants
    const variants = data['variantSpellings']; 
    const uri = data['stable']; 
    displayWrittenVariants(variants); 
    console.log(info, variants); 

    let referenceNode = document.getElementById('relatedTextStats').parentElement; 
    //remove the stable block if it exists: 
    let stableBox = document.getElementById('stableBox'); 
    if(stableBox){stableBox.remove();}
    referenceNode.appendChild(createStableLinkingBlock(id, uri));
  
  }); 

}

$(document).ready(function(){
  addInteractionToEntities();
});
