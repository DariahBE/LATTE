let spellingVariantTracker = [];
function binVariant(elem){
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

function saveNewVariant(){
    
}

function displayET_Variant(data){
    //where to put the box that interacts with variantdata: 
    console.warn('MISSING TARGET DECLARATION IN et_variants.js'); 
    const target = document.getElementById('something'); 
    spellingVariantTracker = [];
    var spellingVariantMainBox = document.createElement('div');
    spellingVariantMainBox.setAttribute('id', 'embeddedSpellingVariants');
    var spellingVariantTitle = document.createElement('h3'); 
    spellingVariantTitle.appendChild(document.createTextNode('Naming variants: '));
    spellingVariantTitle.classList.add('font-bold', 'text-lg', 'items-center', 'flex', 'justify-center');
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
      variantDisplayTex.classList.add('writtenvariantvalue');
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
    console.log(spellingVariantMainBox); 
    return spellingVariantMainBox;
}