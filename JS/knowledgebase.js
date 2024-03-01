var kb = null; 
class KnowledgeBase {
    constructor(et) {
        this.mainKBElement = document.getElementById('urlrelations');
        this.subKBElement = document.getElementById('urlrelationscontainer');
        console.log(this.mainKBElement, this.subKBElement); 
        if(!(et===false)){
            this.displayEntries(et); 
        }
    }
  
    purgecontainer(){
        this.subKBElement.innerHTML = ''; 
    }

    displaySingleEntry(elem){
        //console.log('start processing'); 
        //console.log(elem);
        const partnername = elem.k.properties.partner;
        const kbuuid = elem.k.properties.uid;
        const kblink = elem.k.properties.partner_uri;
        const kb_block = document.createElement('div'); 
        kb_block.classList.add('m-1', 'p-1', 'kbrelationbox', 'bg-green-100', 'flex'); 
        const p_one = document.createElement('p');
        p_one.addEventListener('click', function(){
            window.open(kblink, '_blank');
        }); 
        const p_two = document.createElement('p');
        p_one.classList.add('displayPartnerName'); 
        p_one.setAttribute('data-uuid', kbuuid); 
        p_one.setAttribute('data-link', kblink); 
        p_one.appendChild(document.createTextNode(partnername)); 
        p_two.classList.add('xsbinicon', 'bg-green-200', 'm-1', 'p-1', 'rounded-full'); 
        kb_block.appendChild(p_one);
        kb_block.appendChild(p_two);
        console.log(kb_block); 
        this.subKBElement.appendChild(kb_block); 
    }

    addEntry(key, value) {
      this.data[key] = value;
    }
  
    getEntry(key) {
      return this.data[key];
    }
  
    removeEntry(key) {
      delete this.data[key];
    }
  
    getAllEntries(id) {
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

  }
  
  // Example Usage:
  //const kb = new KnowledgeBase();
  /*
  kb.addEntry('key1', 'value1');
  kb.addEntry('key2', 'value2');
  
  console.log(kb.getEntry('key1')); // Output: value1
  
  kb.removeEntry('key1');
  console.log(kb.getAllEntries()); // Output: { key2: 'value2' }

*/