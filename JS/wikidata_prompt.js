function wdprompt(string, offset = 0){
  let language = document.getElementById('wdlookuplanguage').value;         //&language=language
  let strictLookup = document.getElementById('strictLookup').checked;       //&strictlanguage=true
  let useFallback = document.getElementById('returnSameAsLookup').checked;  //&uselang=language
  if (strictLookup){
    var extra1 = '&strictlanguage=true'; 
  }else{
    var extra1 = '&strictlanguage=false'; 
  }
  if( useFallback){
    var extra2 = '&uselang='+language; 
  }else{
    var extra2 = ''; 
  }

  console.log(language); 
  let by = 10; 
  console.log("wdprompt function needs nan extra dropdown for language swapping"); 
  console.log('use the optional &uselang='+language+' feature to show hits in the language you used to search'); 
  let promptURL = "https://www.wikidata.org/w/api.php?action=wbsearchentities&search="+string+"&origin=*&format=json&errorformat=plaintext&type=item&language="+language+extra1+"&limit="+by+"&continue="+offset+extra2; 
  console.log(promptURL); 
  const target = document.getElementById('wdpromptBox');
  let table = document.createElement('table'); 
  table.classList.add('table-auto'); 
  fetch(promptURL)
  .then((response) => response.json())
  .then((data) => {
    //needs a title item for wikidata:
    var searchResults = data['search'];
    if(searchResults.length === 0){
      target.innerHTML = ''; 
      const noresultmsg = document.createElement('p'); 
      noresultmsg.classList.add('border-t-2', 'mt-1', 'pt-1');
      const noresulttext = document.createTextNode('No results found for the given search query.'); 
      noresultmsg.appendChild(noresulttext); 
      /*let manualCreateButton = document.createElement('button'); 
      let manualCreateButtonText = document.createTextNode('Add manual annotation'); 
      manualCreateButton.appendChild(manualCreateButtonText); */
      target.appendChild(noresultmsg); 
      acceptQID(); 
      return;
    }
    for(var s = 0; s < searchResults.length; s++){
      let qid, title, descr = ''; 
      try{
        qid = searchResults[s]['id'];
      }catch(e){
        qid = false;
      }
      try{
        title = searchResults[s]['display']['label']['value'];
      }catch(e){
        title = '';
      }
      try{
        descr =  searchResults[s]['display']['description']['value'];
      }catch(e){
        descr = ''; 
      }
      if(qid){
        let row = table.insertRow();
        row.classList.add('even:bg-gray-200', 'odd:bg-gray-100', 'hover:bg-sky-200'); 
        let pickThisCell = row.insertCell(); 
        pickThisCell.textContent = '✓'; 
        pickThisCell.addEventListener('click', function(){pickThisQID(qid)}); 
        let qidcell = row.insertCell();
        let qidlink = "https://www.wikidata.org/wiki/"+qid; 
        let qidAnchor = document.createElement('a'); 
        qidAnchor.setAttribute('href', qidlink); 
        qidAnchor.setAttribute('target', '_blank');
        qidAnchor.classList.add('externalURILogo'); 
        qidAnchor.appendChild(document.createTextNode(qid)); 
        qidcell.appendChild(qidAnchor); 
        let titlecell = row.insertCell();
        titlecell.textContent = title;
        let descrcell = row.insertCell();
        descrcell.textContent = descr;
        qidcell.classList.add('m-1', 'p-1');
        titlecell.classList.add('m-1', 'p-1');
        pickThisCell.classList.add('m-1', 'p-1', 'hover:bg-green-200');
        descrcell.classList.add('m-1', 'p-1'); 
      }
    }
    //navigate offsets: 
    var navigateReply = document.createElement('div'); 
    navigateReply.classList.add('flex', 'flex-row'); 
    let nextOffset, prevOffset; 
    //    prev page offset:
    if (offset != 0){
      prevOffset = offset-by; 
      let prevPage = document.createElement('p');
      prevPage.appendChild(document.createTextNode('<<')); 
      prevPage.classList.add('font-bold', 'rounded-full', 'text-2xl', 'bg-sky-400', 'text-center'); 
      prevPage.addEventListener('click', function(){wdprompt(string, prevOffset)}); 
      navigateReply.appendChild(prevPage); 
    }
    //    next page offset: 
    nextOffset = data['search-continue']; 
    if(typeof nextOffset !== "undefined"){
      //there's more than 'by'-results;
      let nextPage = document.createElement('p');
      nextPage.appendChild(document.createTextNode('>>')); 
      nextPage.classList.add('font-bold', 'rounded-full', 'text-2xl', 'bg-sky-400', 'text-center');
      nextPage.addEventListener('click', function(){wdprompt(string, nextOffset)});
      navigateReply.appendChild(nextPage); 
    }
    target.innerHTML = ''; 
    target.appendChild(navigateReply); 
    target.appendChild(table);
  });
}

let chosenQID = null; 

function acceptQID(qid = -1){
  /* Start lookup if an entity in this database has the assigned QID.
    *IF TRUE: it will load the entity
    *IF FALSE: it will suggest you to make a new one.
  */
  if (qid !== -1){
    document.getElementById('embeddedWDConfirmationGroup').remove();
    //BUG10 FIX: async/await promise here. 
    checkIfConnectionExists(qid)
    .then((data)=>{
      //console.warn('checkIfConnectionExists needs to be a promise to fix BUG10!!');
      //console.log(data);
      if (data==0){
        let baseElem = document.getElementById('embeddedET'); 
        //console.warn('related to BUG10: when using the SHOWHIT call; baseElem gets removed at a later stage causing the UI to blink!'); 
        baseElem.classList.remove('hidden'); 
        let creationElement = document.getElementById('etcreate'); 
        creationElement.classList.add('getAttention');
      }else{
        //NO changes needed to DOM in here.
      }
    })
  }
}

function pickThisQID(qid){
  chosenQID = qid;
  //console.log(qid); 
  //clear the promptbox:
  document.getElementById('wdpromptBox').remove();
  //load the wikidata.js class and set qidmode on qid!
  var wd = new wikibaseEntry(qid, wdProperties, 'slideover', 'qid');
  wd.getWikidata()
    .then(function(){wd.renderEntities(qid)});
  //if the user is unsure, allow them to go back to the selector layout: 
  //if the user is SURE ==> provide a save button which sends the request to the server! 
  let rejectButton = document.createElement('button');
  let acceptButton = document.createElement('button');
  rejectButton.classList.add('bg-red-500', 'hover:bg-red-700', 'text-white', 'font-bold', 'py-2', 'px-4', 'rounded');
  acceptButton.classList.add('bg-green-500', 'hover:bg-green-700', 'text-white', 'font-bold', 'py-2', 'px-4', 'rounded');
  let rejectText = document.createTextNode('Reject');
  let acceptText = document.createTextNode('Accept');
  rejectButton.addEventListener('click', function(){
    wd = null; //destroy wikidataObject
    console.log(wd); 
  });
  acceptButton.addEventListener('click', function(){
    acceptQID(qid);
  }); 
  
  const displayWDtarget = document.getElementById('WDResponseTarget');
  rejectButton.appendChild(rejectText);
  acceptButton.appendChild(acceptText);
  let confirmationDiv = document.createElement('div');
  confirmationDiv.setAttribute('id', 'embeddedWDConfirmationGroup');
  confirmationDiv.appendChild(acceptButton);
  confirmationDiv.appendChild(rejectButton);
  document.getElementById('slideoverDynamicContent').insertBefore(confirmationDiv, displayWDtarget);
}

function showHit(id){
  let replaceContent = document.getElementById('displayHitEt'); 
  replaceContent.innerHTML = '';  
  let etPropContainer = document.createElement('div'); 
  etPropContainer.classList.add('w-full'); 
  etPropContainer.setAttribute('id', 'connectSuggestion'); 
  etPropContainer.setAttribute('data-neoid', id); 
  //get mentions of this et and connected texts: 
  console.warn('NEO ID; ', id); 
  replaceContent.appendChild(etPropContainer);
  // if relatedTextStats is missing from the DOM: 
  //race condition in etcreate! Elem does not exist when WD check hasn't been performed.
  waitForElement('#WDResponseTarget').then((elm) => {
    if (!(document.getElementById('relatedTextStats'))){
      var gateWay = document.createElement('div');
      var statsTarget = document.createElement('div');
      statsTarget.setAttribute('id', 'relatedTextStats');
      statsTarget.classList.add('text-gray-600', 'w-full',  'm-2', 'p-2', 'left-0');
      gateWay.appendChild(statsTarget);
      var referenceNode = document.getElementById('WDResponseTarget');//.nextElementSibling;
      referenceNode.parentElement.insertBefore(gateWay, referenceNode);
    }
    //document.getElementById('WDResponseTarget').appendChild(gateWay);
    findRelatedTexts(id);
    //get DB information about this et: 
    showDBInfoFor(id, true); 
  
  });
}

let checkIfConnectionExists = async(qid)=>{
  var existingConnection = new Promise((resolve, reject) =>{
  //make a .fetch call in javascript
  //BUG10 FIX: make this function return a promise
  console.warn('checking if QID exists.'); 
  fetch("/AJAX/checkWDExists.php?qid="+qid)
  .then((response) => response.json())
  .then((data) => {
    console.log(data);
    let hits = data['data'];
    console.warn('testdata still present in code!!'); 
    hits.push(148);
    hits.push(146);
    let j = 0; 
    if(data['hits']!= 0){
      //load the first hit anyway: 
      let maintarget = document.getElementById('embeddedET');
      maintarget.innerHTML = ''; 
      maintarget.classList.remove('hidden');
      let contentNav = document.createElement('div'); 
      contentNav.setAttribute('id', 'navigateETs'); 
      let hitNav = document.createElement('div'); 
      hitNav.setAttribute('id', 'displayHitEt'); 
      maintarget.appendChild(contentNav);   //navigates through the hits.
      maintarget.appendChild(hitNav);       //prepares a box to display single hit info. 
      showHit(hits[j]); 
      //add navigationmenu if there's more than one option: 
      if (hits.length>1){
        function paginationIndicator(p){
          if (p === 0){
            navigateBack.classList.add('invisible');
          }else{
            navigateBack.classList.remove('invisible');
          }
          if(p+1 === hits.length){
            navigateNext.classList.add('invisible');
          }else{
            navigateNext.classList.remove('invisible');
          }
        }
        let navigateHits = document.createElement('p');
        navigateHits.classList.add('w-full'); 
        navigateBack = document.createElement('span'); 
        navigateBack.appendChild(document.createTextNode('<<')); 
        navigateNext = document.createElement('span'); 
        navigateNext.appendChild(document.createTextNode('>>')); 
        navigateState = document.createElement('span');
        navigateState.setAttribute('id', 'optionsIndicatorWD');
        navigateState.appendChild(document.createTextNode((j+1)+' of '+hits.length));
        navigateNext.addEventListener('click', function(){
          if(j+1<hits.length){
            j++; 
            navigateState.innerHTML = '';
            navigateState.appendChild(document.createTextNode((j+1)+' of '+hits.length));
            paginationIndicator(j);
            showHit(hits[j]); 
          }
        })
        navigateBack.addEventListener('click', function(){
          if(j>0){
            j--;
            paginationIndicator(j);
            showHit(hits[j]); 
            navigateState.innerHTML = '';
            navigateState.appendChild(document.createTextNode(j+1+' of '+hits.length));
          }
        }); 
        paginationIndicator(j);
        navigateHits.appendChild(navigateBack);
        navigateHits.appendChild(navigateState);
        navigateHits.appendChild(navigateNext);
        console.log(navigateHits);
        console.log(document.getElementById('navigateETs'));
        document.getElementById('navigateETs').appendChild(navigateHits);
        console.warn('Todo, add navigation for multiple hits!'); 

      }
      console.log(data['hits'], ' hits found; ');
      resolve(data['hits']);
      alert('endofblock reached');
    }else{
      //let the user fill out the entity type and go from there
      //create flash box to prompt attention: 
      //OK
      let creationElement = document.getElementById('etcreate'); 
      creationElement.classList.add('getAttention');
      loadPropertiesOfSelectedType();
      resolve(0);
    }
  })
  })
  //return Promise; 
  return existingConnection;
  }