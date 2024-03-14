var spellingVariantDOMReturn = null; 


function checklogin() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: "../user/AJAX/profilestate.php",
            success: function(result) {
                console.log("RESULT OF LOGIN CHECK!", result['valid']);
                resolve(result['valid']);
            },
            error: function(error) {
                reject(error);
            }
        });
    });
}


class SpellingVariant {
    constructor(data,etid, state) {
        this.userstate = state;
        this.spellingVariantTracker = [];
        console.log(data, etid); 
        this.displayET_variant(data, etid); 
    }

    binVariant(e) {
        /**
         * Deletes a variant from the backend 
         * then deletes it from the global tracker too. 
         */
        //gets the attribute of e: sends XHR request to delete. 
        //fetch a fresh CSRF token: 
        const DOMElement = e.parentElement; 
        let nodeInternalId = DOMElement.getElementsByClassName('writtenvariantvalue')[0].getAttribute('data-neo');
        fetch('/AJAX/getdisposabletoken.php')
            .then((response) => response.json())
            .then((token) => {
                fetch('/AJAX/variants/delete.php?variantid='+nodeInternalId+'&entityid='+this.variantNeoId+'&token='+token)
                .then(data => function(){});
            const writtenValue = DOMElement.textContent; 
            //then removes it from the spellingvarianttracker
            let idx = this.spellingVariantTracker.indexOf(writtenValue); 
            delete(this.spellingVariantTracker[idx]); 
            //then removes it from the DOM: 
            e.parentElement.remove();
            })
    }

    purgeVariantBox() {
        /**     Dormant code. 
         * When called: empties the variant display and clears the global tracker 
         * You need to purge the tracker when looping back and forth over a few 
         * entities. 
         */
        this.spellingVariantTracker = []; 
        let cleanupVarbox = document.getElementById('variantStorageBox'); 
        console.log(cleanupVarbox); 
        if (cleanupVarbox !== null){
        cleanupVarbox.innerHTML = ''; 
        }
    }

    addVariantInBox(varname, uid, nid, user) {
        /**
         * When a variant is given, it'll put the string in a DOMelement
         * and adds the UUID (uid) and internal NEO ID (nid) to it as attributes
         * for helping with delete operations. 
         */
        const classScope = this; 
        if(this.spellingVariantTracker.includes(varname)){
            return;
        }
        this.spellingVariantTracker.push(varname);
        var storeIn = document.getElementById('variantStorageBox'); 
        var variantDisplayDiv = document.createElement('div'); 
        variantDisplayDiv.classList.add('m-1','p-1','spellingvariantbox', 'bg-amber-100', 'flex');
        var variantDisplayTex = document.createElement('p');
        variantDisplayTex.classList.add('writtenvariantvalue');
        variantDisplayTex.setAttribute('data-neo', nid);
        variantDisplayTex.setAttribute('data-uuid', uid);
        variantDisplayTex.setAttribute('data-label', varname);
        variantDisplayTex.appendChild(document.createTextNode(varname));
        variantDisplayDiv.appendChild(variantDisplayTex);
        if(user){
            var variantDisplayBin = document.createElement('p');
            variantDisplayBin.classList.add('xsbinicon', 'bg-amber-200', 'm-1','p-1', 'rounded-full'); 
            variantDisplayBin.addEventListener('click', function(){classScope.binVariant(this);});
            variantDisplayDiv.appendChild(variantDisplayBin);
            }
        storeIn.appendChild(variantDisplayDiv);
    }

    preprocess_variants(data) {
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

    neoVarsToDom(variants, preprocess = 0) {
        let internal_variants = variants; 
        if (preprocess == 1){
            internal_variants = this.preprocess_variants(variants); 
        }
        //if there are known variant spellings in the DOM: put them in the boxes at load. 
        //use neo4Jid and UID to identify variant labels. 
        if(internal_variants.length > 0 && 'labelVariants' in internal_variants[0]){
            internal_variants[0]['labelVariants'].forEach(element => {
                this.addVariantInBox(element['value'], element['uid'], element['neoID'], this.userstate);
            });
        }
        }


variantNeoId = -1;

displayET_variant(data, relatedET) {
    //NEEDS A PROMISE
    console.warn('YOU ARE HERE');
    console.log(data, relatedET);
    this.variantNeoId = relatedET;
    return new Promise((resolve, reject) => {
        let classScope = this;
        const target = document.getElementById('etVariantsTarget');
        this.spellingVariantTracker = [];
        var spellingVariantMainBox = document.createElement('div');
        spellingVariantMainBox.setAttribute('id', 'embeddedSpellingVariants');
        console.log(spellingVariantMainBox);
        let spellingVariantTitle = createDivider('Naming variants: ');
        spellingVariantMainBox.appendChild(spellingVariantTitle);
        spellingVariantMainBox.classList.add('border-solid', 'border-2', 'border-black-800', 'rounded-md', 'flex-grow');

        if (classScope.userstate) {
            var spellingVariantCreation = document.createElement('input');
            spellingVariantCreation.setAttribute('id', 'variantInputBox');
            spellingVariantCreation.classList.add('border-solid', 'border-2');
            var addToStorageBox = document.createElement('button');
            addToStorageBox.appendChild(document.createTextNode('Add'));
            addToStorageBox.addEventListener('click', function () {
                let writtenValue = document.getElementById('variantInputBox').value;
                if (classScope.spellingVariantTracker.includes(writtenValue)) {
                    return;
                } else {
                    fetch("/AJAX/getdisposabletoken.php")
                        .then(response => response.json())
                        .then(data => {
                            const token = data;
                            fetch("/AJAX/variants/make.php?varlabel=" + writtenValue + "&entity=" + relatedET + "&token=" + token)
                                .then(response => response.json())
                                .then(data => {
                                    let nid = data['data']['nid'];
                                    let uuid = data['data']['uuid'];
                                    document.getElementById('variantInputBox').value = '';
                                    classScope.addVariantInBox(writtenValue, uuid, nid, classScope.userstate);
                                    resolve(spellingVariantMainBox); // Resolve promise when operation is complete
                                })
                                .catch(error => {
                                    reject(error); // Reject promise if there is an error
                                });
                        })
                        .catch(error => {
                            reject(error); // Reject promise if there is an error
                        });
                }
            });
            spellingVariantMainBox.appendChild(spellingVariantCreation);
            spellingVariantMainBox.appendChild(addToStorageBox);
        }

        var spellingVariantSubBox = document.createElement('div');
        spellingVariantSubBox.setAttribute('id', 'variantStorageBox');
        spellingVariantSubBox.classList.add('flex', 'border-t-2', 'border-t-dashed', 'flex-wrap');

        spellingVariantMainBox.appendChild(spellingVariantSubBox);
        target.appendChild(spellingVariantMainBox);
        if (data !== null && relatedET !== null) {
            this.neoVarsToDom(data);
        }
        console.warn('SETTING CONTENT');
        this.htmlcontent = spellingVariantMainBox;
    });
}





//const spellingVariantObject = new SpellingVariantTracker();

     get_HTML_content(){
         //alert('CALLD'); 
         return this.htmlcontent;
     }


}

