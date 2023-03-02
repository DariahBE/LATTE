function wdprompt(string, language='en', offset = 0){
  let by = 10; 
  let promptURL = "https://www.wikidata.org/w/api.php?action=wbsearchentities&search="+string+"&origin=*&format=json&errorformat=plaintext&type=item&language="+language+"&strictlanguage=false&limit="+by+"&continue="+offset; 
  const target = document.getElementById('wdpromptBox');
  let table = document.createElement('table'); 
  fetch(promptURL)
  .then((response) => response.json())
  .then((data) => {
    var searchResults = data['search'];
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
        let qidcell = row.insertCell();
        qidcell.textContent = qid;
        let titlecell = row.insertCell();
        titlecell.textContent = title;
        let descrcell = row.insertCell();
        descrcell.textContent = descr;
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
      prevPage.addEventListener('click', function(){wdprompt(string, language, prevOffset)}); 
      navigateReply.appendChild(prevPage); 
    }
    //    next page offset: 
    nextOffset = data['search-continue']; 
    if(typeof nextOffset !== "undefined"){
      //there's more than 'by'-results;
      let nextPage = document.createElement('p');
      nextPage.appendChild(document.createTextNode('>>')); 
      nextPage.classList.add('font-bold', 'rounded-full', 'text-2xl', 'bg-sky-400', 'text-center'); 
      nextPage.addEventListener('click', function(){wdprompt(string, language, nextOffset)});   
      navigateReply.appendChild(nextPage); 
    }
    target.innerHTML = ''; 
    target.appendChild(navigateReply); 
    target.appendChild(table);
  });
}

function acceptThisQID(qid){
  //clear the promptbox:

  //load the wikidata.js class and set qidmode on qid!

  //put qid in field - make it non-editable. 
}