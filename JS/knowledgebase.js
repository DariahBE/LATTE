var kb = null; 
let neoIdOfEt = null;


class KnowledgeBase {
    constructor(et, state) {
        this.userstate = state;
        //console.log("RECEIVED STATE IS", et, state); 
        this.addNewButton = document.getElementById('add_kb_relation'); 
        this.mainKBElement = document.getElementById('urlrelations');
        this.subKBElement = document.getElementById('urlrelationscontainer');
        this.addNewButton.style.display = 'none'; 
        this.addNewButton.disabled = true; 

        // console.log(et,state); 
        if(!(et===false)){
            this.displayEntries(et); 
            this.neoIdOfEt = et; 
            if(this.userstate){
                this.addNewButton.disabled = false; 
                this.addNewButton.style.display = ''; 

            }
        }
        this.addNewButton.addEventListener('click', () => {
            this.buildCreationDisplay(); 
        });
    }
/*

    purgecontainer(){
        //needed?
        this.subKBElement.innerHTML = ''; 
    }
*/

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
        // console.log("thisloop userstate", this.userstate); 
        const partnername = elem.k.properties.partner;
        const kbuuid = elem.k.properties.uid;
        const kblink = elem.k.properties.partner_uri;
        const kbneoid = elem.k.id; 
        const kb_block = document.createElement('div'); 
        kb_block.classList.add('m-1', 'p-1', 'kbrelationbox', 'bg-green-100', 'flex'); 
        const p_one = document.createElement('p');
        p_one.addEventListener('click', function(){
            window.open(kblink, '_blank');
        }); 
        p_one.classList.add('displayPartnerName'); 
        p_one.setAttribute('data-neo', kbneoid); 
        p_one.setAttribute('data-uuid', kbuuid); 
        p_one.setAttribute('data-link', kblink); 

        p_one.appendChild(document.createTextNode(partnername)); 

        kb_block.appendChild(p_one);
        // console.log('CLASSCOPE IN EVENTLOOP', classScope.userstate); 
        if(classScope.userstate){
            const p_two = document.createElement('p');
            p_two.classList.add('xsbinicon', 'bg-green-200', 'm-1', 'p-1', 'rounded-full'); 
            p_two.addEventListener('click', function(){
                classScope.binkb(this); 
            })
        kb_block.appendChild(p_two);
        }
        // console.log(kb_block); 
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
        relationBox.className = 'm-1 p-1 kbrelationbox bg-green-100 flex';
        // Create a kb box subelement for the display partner name
        const displayPartnerName = document.createElement('p');
        displayPartnerName.className = 'displayPartnerName';
        displayPartnerName.setAttribute('data-neo', data.kb_neo_id);
        displayPartnerName.setAttribute('data-uuid', data.kb_uuid);
        displayPartnerName.setAttribute('data-link', data.kb_uri);
        displayPartnerName.textContent = data.kb_label;
        displayPartnerName.addEventListener('click', function(){
            window.open(data.kb_uri, '_blank');
        })
        // Create a kb box subelement for the XS bin icon
        const xsBinIcon = document.createElement('p');
        xsBinIcon.className = 'xsbinicon bg-green-200 m-1 p-1 rounded-full';
        xsBinIcon.addEventListener('click', () => {
            this.binkb(xsBinIcon); 
        });
        // Append kb relation to the relation box
        relationBox.appendChild(displayPartnerName);
        relationBox.appendChild(xsBinIcon);
        document.getElementById('urlrelationscontainer').appendChild(relationBox); 
    }

    handleSubmit() {
        // Handle form submission
        //get Label:
        const labelstring = document.getElementById('new_kb_name_field').value;
        // get url: 
        const url = document.getElementById('new_kb_url_field').value; 
        const submitButton = document.getElementById('submitBtn_kb'); 
        const self = this; 
        //fetch a token
        fetch('/AJAX/getdisposabletoken.php?task=1')
        .then((response) => response.json())
        .then((token) => {
          
            // Prepare data for POST request
            const postData = {
                token: token,
                id: this.neoIdOfEt,
                label: labelstring, 
                uri: url
            };
            //console.log(postData); 
            // Define fetch options for POST request
            const options = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(postData)
            };

            $.post("/AJAX/fetch_kb.php?mode=create&token=" + token + "&id=" + this.neoIdOfEt, { data: postData })
                .then(function( data ) {
                    self.appendToDom(data); 
                    //console.log(data); 
            })

        })
        this.handleClose();
    }
    
    handleClose() {
        const modal = document.getElementById('modal');
        const formContainer = document.getElementById('formContainer');
        modal.remove();
        formContainer.remove();
    }

    buildCreationDisplay(){
        if(!(this.userstate)){return;}
        // TODO disable submitaction and creataction when entityID is missing!!! 
        //
        //builds a two-field form: partner name and the URI
        // Create modal background
        const self = this;
        const modalBackground = document.createElement('div');
        modalBackground.id = 'modal';
        modalBackground.className = 'fixed inset-0 bg-black opacity-50 z-50';
        document.body.appendChild(modalBackground);

        // Create form container
        const formContainer = document.createElement('div');
        formContainer.id = 'formContainer';
        formContainer.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white p-6 rounded-lg shadow-md z-50';
        
        // Create form
        const form = document.createElement('form');
        form.id = 'myForm';
        form.className = 'space-y-4';

        //create nameDiv = 
        const nameDiv = document.createElement('div'); 
        nameDiv.classList.add('w-full');
        // Create name input field
        const nameLabel = document.createElement('label');
        nameLabel.htmlFor = 'name';
        nameLabel.textContent = 'Name:';
        //form.appendChild(nameLabel);
        nameDiv.appendChild(nameLabel);

        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.id = 'new_kb_name_field';
        nameInput.name = 'name';
        nameInput.className = 'border rounded-md px-2 py-1';
        nameDiv.appendChild(nameInput);
        form.appendChild(nameDiv)

        //url Div: 
        const urlDiv = document.createElement('div'); 
        urlDiv.classList.add('w-full');
        // Create URL input field
        const urlLabel = document.createElement('label');
        urlLabel.htmlFor = 'url';
        urlLabel.textContent = 'URL:';
        urlDiv.appendChild(urlLabel);

        const urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.id = 'new_kb_url_field';
        urlInput.name = 'url';
        urlInput.className = 'border rounded-md px-2 py-1';
        urlDiv.appendChild(urlInput);
        form.appendChild(urlDiv); 

        // Create submit and close buttons
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'flex justify-between';

        const submitButton = document.createElement('button');
        submitButton.type = 'button';
        submitButton.id = 'submitBtn_kb';
        submitButton.className = 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600';
        submitButton.textContent = 'Submit';
        buttonContainer.appendChild(submitButton);

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.id = 'closeBtn_kb';
        closeButton.className = 'bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600';
        closeButton.textContent = 'Close';
        buttonContainer.appendChild(closeButton);

        form.appendChild(buttonContainer);
        formContainer.appendChild(form);

        document.body.appendChild(formContainer);

        // Add event listeners
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
