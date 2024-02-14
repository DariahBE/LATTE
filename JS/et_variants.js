let spellingVariantTracker = [];
function binVariant(e){
    /**
     * Deletes a variant from the backend 
     * then deletes it from the global tracker too. 
     */
    //gets the attribute of e: sends XHR request to delete. 
    const DOMElement = e.parentElement; 
    let nodeInternalId = DOMElement.getElementsByClassName('writtenvariantvalue')[0].getAttribute('data-neo');
    fetch('/AJAX/variants/delete.php?variantid='+nodeInternalId+'&entityid='+variantNeoId)
        .then(data => function(){});
    const writtenValue = DOMElement.textContent; 
    //then removes it from the spellingvarianttracker
    let idx = spellingVariantTracker.indexOf(writtenValue); 
    delete(spellingVariantTracker[idx]); 
    //tehn removes it from the DOM: 
    e.parentElement.remove();
}

function purgeVariantBox() {
    /**
     * When called: empties the variant display and clears the global tracker 
     * You need to purge the tracker when looping back and forth over a few 
     * entities. 
     */
    spellingVariantTracker = []; 
    let cleanupVarbox = document.getElementById('variantStorageBox'); 
    console.log(cleanupVarbox); 
    if (cleanupVarbox !== null){
      cleanupVarbox.innerHTML = ''; 
    }
}

function addVariantInBox(varname, uid, nid){
    /**
     * When a variant is given, it'll put the string in a DOMelement
     * and adds the UUID (uid) and internal NEO ID (nid) to it as attributes
     * for helping with delete operations. 
     */
    if(spellingVariantTracker.includes(varname)){
        return;
      }
      spellingVariantTracker.push(varname);
      var storeIn = document.getElementById('variantStorageBox'); 
      var variantDisplayDiv = document.createElement('div'); 
      variantDisplayDiv.classList.add('m-1','p-1','spellingvariantbox', 'bg-amber-100', 'flex');
      var variantDisplayTex = document.createElement('p');
      variantDisplayTex.classList.add('writtenvariantvalue');
      variantDisplayTex.setAttribute('data-neo', nid);
      variantDisplayTex.setAttribute('data-uuid', uid);
      variantDisplayTex.setAttribute('data-label', varname);
      variantDisplayTex.appendChild(document.createTextNode(varname));
      var variantDisplayBin = document.createElement('p');
      variantDisplayBin.classList.add('xsbinicon', 'bg-amber-200', 'm-1','p-1', 'rounded-full'); 
      variantDisplayBin.addEventListener('click', function(){binVariant(this);});
      variantDisplayDiv.appendChild(variantDisplayTex);
      variantDisplayDiv.appendChild(variantDisplayBin);
      storeIn.appendChild(variantDisplayDiv);
}

function preprocess_variants(data){
    /**
     *  Helper function for neoVarsToDom: when variants aren't structured as it is expected,
     *  this function will restructure them to fit the basic logic. 
     */
    let repl = [] 
    data.forEach( variant => {
        console.log(variant)
        let varuid = variant['primary'][1]; 
        let varstring = variant['label']; 
        let varneo = variant['neoid']; 
        repl.push({
            'DOMstring': 'Label', 
            'value': varstring,
            'uid': varuid, 
            'neoID': varneo
        })
    }); 
    return [{'labelVariants': repl}];
}

function neoVarsToDom(variants, preprocess = 0){
    /**
     * Expects a specifically encoded dict with information of known spelling variants
     * puts them in the DOM with the help of 
     *  - preprocess_variants (for poorly structured data)
     *  - addVariantInBox( builds the DOM elements) 
     */
    let internal_variants = variants; 
    if (preprocess == 1){
        internal_variants = preprocess_variants(variants); 
    }
    //if there are known variant spellings in the DOM: put them in the boxes at load. 
    //use neo4Jid and UID to identify variant labels. 
    if(internal_variants.length > 0 && 'labelVariants' in internal_variants[0]){
        internal_variants[0]['labelVariants'].forEach(element => {
            addVariantInBox(element['value'], element['uid'], element['neoID']);
        });
    }
}
let variantNeoId = -1;
function displayET_variant(data, relatedET){
    variantNeoId = relatedET; 
    //required is the extra nod ID (relatedET)
    // TODO: process data: ==> use neoVarsToDom!
    //where to put the box that interacts with variantdata: 
    //etVariantsTarget is the only ID you should use to display variant collection. 
    const target = document.getElementById('etVariantsTarget'); 
    spellingVariantTracker = [];
    var spellingVariantMainBox = document.createElement('div');
    spellingVariantMainBox.setAttribute('id', 'embeddedSpellingVariants');
    let spellingVariantTitle = createDivider('Naming variants: '); 
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
      let writtenValue = document.getElementById('variantInputBox').value; 
        if(spellingVariantTracker.includes(writtenValue)){
            return;
        }else{
            //BUG: CAREFULL!!! if relatedET === FALSE you'll need to submit a new entity node first!!
            //      might be prone to race conditions (test this!)
            //send it to BE ==> return the NEOID and UID!
            //first get a token from the DB: 
            fetch("/AJAX/getdisposabletoken.php")
            .then(response => response.json())
            .then(data => {
              const token = data;
                //do a call to AJAX/variants/make.php
                // pass two URL arguments: 
                //     $_GET['varlabel'] ==> for the written variant label
                //     (int)$_GET['entity'] ==> for the Neo ID of the enity
                fetch("/AJAX/variants/make.php?varlabel="+writtenValue+"&entity="+relatedET+"&token="+token)
                .then(response => response.json())
                .then(data => {
                    let nid = data['data']['nid']; 
                    let uuid = data['data']['uuid'];
                    document.getElementById('variantInputBox').value = ''; 
                    addVariantInBox(writtenValue, uuid, nid); //TODO: push to server, get UUID en NID in return
                });
            });
        }
    }); 
    spellingVariantMainBox.appendChild(spellingVariantCreation);
    spellingVariantMainBox.appendChild(addToStorageBox);
    spellingVariantMainBox.appendChild(spellingVariantSubBox);
    target.appendChild(spellingVariantMainBox); 
    if (data !== null && relatedET !== null){
        //if no data is passed; only generate an empty box with all functionality. 
        neoVarsToDom(data);
    }

    return spellingVariantMainBox;
}