var kb = null; 
let neoIdOfEt = null;


class KnowledgeBase {
    constructor(et, state) {
        //et = NEO4J int id of the entity a Knowledgebase node is connected to
        //state = user id (integer) of the user who is created the new KB record. 
        this.userstate = state;
        //console.log("RECEIVED STATE IS", et, state); 
        this.addNewButton = document.getElementById('add_kb_relation'); 
        this.mainKBElement = document.getElementById('urlrelations');
        this.subKBElement = document.getElementById('urlrelationscontainer');

        const hasNonCore = (typeof window.hasNonCoreEntities !== 'undefined')
          ? Boolean(window.hasNonCoreEntities)
          : (typeof coreNodes !== 'undefined' && coreNodes.length > 0);

        if (!hasNonCore && this.mainKBElement) {
            this.mainKBElement.classList.add('hidden');
            this.mainKBElement.style.setProperty('display', 'none', 'important');
        }
        this.addNewButton.style.display = 'none'; 
        this.addNewButton.disabled = true; 
        // console.log(et,state); 
        if(!(et===false) && hasNonCore){
            this.displayEntries(et); 
            this.neoIdOfEt = et; 
            if(this.userstate){
                this.addNewButton.disabled = false; 
                this.addNewButton.style.display = ''; 

            }
        }else{
            console.warn('HIDING KBRELATIONS due to missing value for ET!'); 
            this.mainKBElement.classList.add('hidden');
        }
        this.addNewButton.addEventListener('click', () => {
            this.buildCreationDisplay(); 
        });
    }

    binkb(e){
    /**
     * Deletes a kb link from the backend 
     * then deletes it from the global tracker too. 
    */
    //gets the attribute of e: sends XHR request to delete. 
    //fetch a fresh CSRF token: 
    const DOMElement = e.parentElement; 
    let nodeInternalId = DOMElement.getElementsByClassName('displayPartnerName')[0].getAttribute('data-neo');
    fetch('/AJAX/getdisposabletoken.php')
        .then((response) => response.json())
        .then((token) => {
            fetch('/AJAX/fetch_kb.php?mode=delete&token='+token+'&kbid='+nodeInternalId+'&id='+this.neoIdOfEt)
            .then(data => function(){});
        const writtenValue = DOMElement.textContent; 
        //then removes it from the DOM: 
        e.parentElement.remove();
        })
    }


    displaySingleEntry(elem){
        const classScope = this; 
        const partnername = elem.k.properties.partner;
        const kbuuid = elem.k.properties.uid;
        const kblink = elem.k.properties.partner_uri;
        const kbneoid = elem.k.id; 
        const kb_block = document.createElement('div'); 
        kb_block.className = 'm-1 px-2.5 py-1 kbrelationbox inline-flex items-center gap-1.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200/90 shadow-2xs hover:bg-emerald-100/70 transition'; 
        const p_one = document.createElement('p');
        p_one.addEventListener('click', function(){
            window.open(kblink, '_blank');
        }); 
        p_one.className = 'displayPartnerName cursor-pointer hover:underline inline-flex items-center gap-1'; 
        p_one.setAttribute('data-neo', kbneoid); 
        p_one.setAttribute('data-uuid', kbuuid); 
        p_one.setAttribute('data-link', kblink); 
        p_one.innerHTML = '<i class="fas fa-external-link-alt text-[10px] text-emerald-600 opacity-80"></i> <span>' + partnername + '</span>';

        kb_block.appendChild(p_one);
        if(classScope.userstate){
            const p_two = document.createElement('button');
            p_two.type = 'button';
            p_two.className = 'xsbinicon ml-1 p-0.5 rounded text-emerald-600 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer flex items-center justify-center'; 
            p_two.title = 'Remove relation';
            p_two.innerHTML = '<i class="fas fa-times text-[11px]"></i>';
            p_two.addEventListener('click', function(){
                classScope.binkb(this); 
            });
            kb_block.appendChild(p_two);
        }
        this.subKBElement.appendChild(kb_block); 
    }
  
    getAllEntries(id) {
        this.neoIdOfEt = id; 
        return fetch('/AJAX/fetch_kb.php?id=' + id)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                return data;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    async displayEntries(id) {
        try {
            let data = await this.getAllEntries(id);
            //you lose this context in foreach!
            for ( let i = 0; i < data.length; i++ ){
                this.displaySingleEntry(data[i]); 
            }

        } catch (error) {
            console.error('Error fetching entries:', error);
        }
    }

    appendToDom(data){
        // Create a div element for the relation box
        const relationBox = document.createElement('div');
        relationBox.className = 'm-1 px-2.5 py-1 kbrelationbox inline-flex items-center gap-1.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200/90 shadow-2xs hover:bg-emerald-100/70 transition';
        // Create a kb box subelement for the display partner name
        const displayPartnerName = document.createElement('p');
        displayPartnerName.className = 'displayPartnerName cursor-pointer hover:underline inline-flex items-center gap-1';
        displayPartnerName.setAttribute('data-neo', data.kb_neo_id);
        displayPartnerName.setAttribute('data-uuid', data.kb_uuid);
        displayPartnerName.setAttribute('data-link', data.kb_uri);
        displayPartnerName.innerHTML = '<i class="fas fa-external-link-alt text-[10px] text-emerald-600 opacity-80"></i> <span>' + data.kb_label + '</span>';
        displayPartnerName.addEventListener('click', function(){
            window.open(data.kb_uri, '_blank');
        });
        // Create a kb box subelement for the XS bin icon
        const xsBinIcon = document.createElement('button');
        xsBinIcon.type = 'button';
        xsBinIcon.className = 'xsbinicon ml-1 p-0.5 rounded text-emerald-600 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer flex items-center justify-center';
        xsBinIcon.title = 'Remove relation';
        xsBinIcon.innerHTML = '<i class="fas fa-times text-[11px]"></i>';
        xsBinIcon.addEventListener('click', () => {
            this.binkb(xsBinIcon); 
        });
        // Append kb relation to the relation box
        relationBox.appendChild(displayPartnerName);
        relationBox.appendChild(xsBinIcon);
        document.getElementById('urlrelationscontainer').appendChild(relationBox); 
    }

    handleSubmit() {
        function isValidURL(url) {
            try {
                new URL(url);
                return true; // URL is valid
            } catch (e) {
                return false; // URL is invalid
            }
        }
        
        // Handle form submission
        //get Label:
        const labelstring = document.getElementById('new_kb_name_field').value;
        // get url: 
        const url = document.getElementById('new_kb_url_field').value; 
        //validate the URL before starting submission event: 
        const validURL = isValidURL(url); 
        const submitButton = document.getElementById('submitBtn_kb'); 
        if(validURL){
            const self = this; 
            //fetch a token
            fetch('/AJAX/getdisposabletoken.php?task=1')
            .then((response) => response.json())
            .then((token) => {
                const formData = new FormData();
                formData.append('data[id]', this.neoIdOfEt);
                formData.append('data[label]', labelstring);
                formData.append('data[uri]', url);

                fetch("/AJAX/fetch_kb.php?mode=create&token=" + token + "&id=" + this.neoIdOfEt, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    self.appendToDom(data); 
                });
            })
            this.handleClose();
        }else{
            const urlElement = document.getElementById('new_kb_url_field'); 
            urlElement.classList.add('animate-shake')
            urlElement.addEventListener("animationend", function () {
                urlElement.classList.remove('animate-shake');
              }, false);
        }

    }
    
    handleClose() {
        const modal = document.getElementById('modal');
        const formContainer = document.getElementById('formContainer');
        modal.remove();
        formContainer.remove();
    }

    buildCreationDisplay(){
        if(!(this.userstate)){return;}
        // Builds a two-field form: partner name and the URI
        // Create modal background
        const self = this;
        const modalBackground = document.createElement('div');
        modalBackground.id = 'modal';
        modalBackground.className = 'fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 transition-opacity';
        document.body.appendChild(modalBackground);

        // Create form container
        const formContainer = document.createElement('div');
        formContainer.id = 'formContainer';
        formContainer.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white p-6 rounded-2xl shadow-2xl border border-slate-200 z-50 w-full max-w-md';
        
        // Modal header
        const headerDiv = document.createElement('div');
        headerDiv.className = 'mb-4';
        const title = document.createElement('h3');
        title.className = 'text-base font-bold text-slate-800';
        title.textContent = 'Add knowledge base link';
        const subtitle = document.createElement('p');
        subtitle.className = 'text-xs text-slate-500 mt-0.5';
        subtitle.textContent = 'Connect this entity to an external authority or encyclopedia.';
        headerDiv.appendChild(title);
        headerDiv.appendChild(subtitle);
        formContainer.appendChild(headerDiv);

        // Create form
        const form = document.createElement('form');
        form.id = 'myForm';
        form.className = 'space-y-4';

        // Create nameDiv
        const nameDiv = document.createElement('div'); 
        nameDiv.className = 'w-full';
        const nameLabel = document.createElement('label');
        nameLabel.htmlFor = 'new_kb_name_field';
        nameLabel.className = 'block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5';
        nameLabel.textContent = 'Authority / partner name:';
        nameDiv.appendChild(nameLabel);

        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.id = 'new_kb_name_field';
        nameInput.name = 'name';
        nameInput.placeholder = 'e.g. VIAF, GeoNames, DNB';
        nameInput.className = 'w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-hidden transition';
        nameDiv.appendChild(nameInput);
        form.appendChild(nameDiv);

        // Create urlDiv
        const urlDiv = document.createElement('div'); 
        urlDiv.className = 'w-full';
        const urlLabel = document.createElement('label');
        urlLabel.htmlFor = 'new_kb_url_field';
        urlLabel.className = 'block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5';
        urlLabel.textContent = 'Target URI:';
        urlDiv.appendChild(urlLabel);

        const urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.id = 'new_kb_url_field';
        urlInput.name = 'url';
        urlInput.placeholder = 'https://...';
        urlInput.className = 'w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-hidden transition';
        urlDiv.appendChild(urlInput);
        form.appendChild(urlDiv); 

        // Create submit and close buttons
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'flex items-center justify-end gap-2 pt-2';

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.id = 'closeBtn_kb';
        closeButton.className = 'px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition cursor-pointer';
        closeButton.textContent = 'Cancel';
        buttonContainer.appendChild(closeButton);

        const submitButton = document.createElement('button');
        submitButton.type = 'button';
        submitButton.id = 'submitBtn_kb';
        submitButton.className = 'px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition cursor-pointer';
        submitButton.textContent = 'Add link';
        buttonContainer.appendChild(submitButton);

        form.appendChild(buttonContainer);
        formContainer.appendChild(form);

        document.body.appendChild(formContainer);

        // Add event listeners
        modalBackground.addEventListener('click', function(){
            self.handleClose();
        });
        submitButton.addEventListener('click', function(){
            self.handleSubmit();
        });
        closeButton.addEventListener('click', function(){
            self.handleClose();
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
                self.handleClose();
            }
        });
    }

}
