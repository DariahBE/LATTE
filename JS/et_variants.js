var spellingVariantDOMReturn = null; 





class SpellingVariant {
    constructor(data,etid, state) {
        this.userstate = state;
        this.spellingVariantTracker = [];
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
                const deleteBody = new URLSearchParams();
                deleteBody.append('variantid', nodeInternalId);
                deleteBody.append('entityid', this.variantNeoId);
                deleteBody.append('token', token);
                fetch('/AJAX/variants/delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: deleteBody
                })
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
        variantDisplayDiv.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 text-amber-900 border border-amber-200/80 mr-1.5 mb-1.5 shadow-2xs';
        var variantDisplayTex = document.createElement('span');
        variantDisplayTex.classList.add('writtenvariantvalue');
        variantDisplayTex.setAttribute('data-neo', nid);
        variantDisplayTex.setAttribute('data-uuid', uid);
        variantDisplayTex.setAttribute('data-label', varname);
        variantDisplayTex.appendChild(document.createTextNode(varname));
        variantDisplayDiv.appendChild(variantDisplayTex);
        if(user){
            var variantDisplayBin = document.createElement('button');
            variantDisplayBin.setAttribute('type', 'button');
            variantDisplayBin.className = 'text-amber-500 hover:text-rose-600 transition ml-1 cursor-pointer'; 
            variantDisplayBin.innerHTML = '<i class="fas fa-times text-[10px]"></i>';
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
    this.variantNeoId = relatedET;
    return new Promise((resolve, reject) => {
        let classScope = this;
        const target = document.getElementById('etVariantsTarget');
        this.spellingVariantTracker = [];
        var spellingVariantMainBox = document.createElement('div');
        spellingVariantMainBox.setAttribute('id', 'embeddedSpellingVariants');
        let spellingVariantTitle = createDivider('Naming variants: ');
        spellingVariantMainBox.appendChild(spellingVariantTitle);
        spellingVariantMainBox.className = 'border border-slate-200/90 rounded-xl p-3.5 bg-slate-50/50 mb-3 flex-grow';
        //TODO: if there's no value set for relatedET, then add should be disabled!
        if (classScope.userstate) {
            let inputGroup = document.createElement('div');
            inputGroup.className = 'flex items-center gap-2 mb-3';
            var spellingVariantCreation = document.createElement('input');
            spellingVariantCreation.setAttribute('id', 'variantInputBox');
            spellingVariantCreation.placeholder = 'Add new variant...';
            spellingVariantCreation.className = 'flex-1 text-xs border border-slate-300 rounded-lg px-3 py-1.5 bg-white shadow-2xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';
            var addToStorageBox = document.createElement('button');
            addToStorageBox.className = 'px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-2xs transition';
            addToStorageBox.appendChild(document.createTextNode('Add'));
            addToStorageBox.addEventListener('click', function () {
                let writtenValue = document.getElementById('variantInputBox').value;
                if (classScope.spellingVariantTracker.includes(writtenValue)) {
                    return;
                } else {
                    fetch("/AJAX/getdisposabletoken.php?task=1")
                        .then(response => response.json())
                        .then(data => {
                            const token = data;
                            const makeBody = new URLSearchParams();
                            makeBody.append('varlabel', writtenValue);
                            makeBody.append('entity', relatedET);
                            makeBody.append('token', token);
                            fetch("/AJAX/variants/make.php", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: makeBody
                            })
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
            if (relatedET == null){
                console.warn('HIDING VARBOX - missing entity.'); 
                //do not yet make a variantbox if there's no entity to link the variant to!
                addToStorageBox.disabled = true;
                spellingVariantMainBox.classList.add('hidden');
            }
            inputGroup.appendChild(spellingVariantCreation);
            inputGroup.appendChild(addToStorageBox);
            spellingVariantMainBox.appendChild(inputGroup);
        }

        var spellingVariantSubBox = document.createElement('div');
        spellingVariantSubBox.setAttribute('id', 'variantStorageBox');
        spellingVariantSubBox.className = 'flex flex-wrap items-center pt-2.5 border-t border-slate-200/80';

        spellingVariantMainBox.appendChild(spellingVariantSubBox);
        target.appendChild(spellingVariantMainBox);
        if (data !== null && relatedET !== null) {
            this.neoVarsToDom(data);
        }
        this.htmlcontent = spellingVariantMainBox;
    });
}





//const spellingVariantObject = new SpellingVariantTracker();

     get_HTML_content(){
         return this.htmlcontent;
     }


}

