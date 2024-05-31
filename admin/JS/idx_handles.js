function attach_dom_idx_triggers(){
    /**
     * Attaches the click event listeners to all elements in the DOM
     * that have the idxBolt class attached to them. idxBolt is the 
     * icon which is next to a Node property. 
     */

    const elements = document.querySelectorAll('.idxBolt');

    elements.forEach(element => {
      element.addEventListener('click', () => {
        // handle click event here
        update_idx_trigger(); 
      });
    });
}


function update_idx_trigger(){
    /**  Listens to DOM click events
     * will send POST requests to add and remove non-composite indices on label.property 
     * values in the CYPHER database. Uses a CSRF token per request - endpoint is 
     * unreachable for non-admin users, even with a token. 
     * 
     * On each succesfull update of the indexes, the frontend is updated and the
     * visual indicator for the index receives an update. 
     */
    let e = event.src || event.target; 
    let label = e.getAttribute('data_nodelabel'); 
    let property = e.getAttribute('data_nodeprop') ;
    let idxname = e.getAttribute('data_idxname');
    let has_idx = e.classList.contains('hasIndex');
    let action = 'drop'; 
    if (has_idx === false){
        action = 'add';
    }

    // console.log(label, property, idxname, has_idx); 
    fetch("/AJAX/getdisposabletoken.php")
    .then(response => response.json())
    .then(data => {
        let submissiondata = {}; 
        const url = "/admin/ajax/update_index.php"; 
        submissiondata['token'] = data; 
        submissiondata['label'] = label;
        submissiondata['prop'] = property;
        submissiondata['action'] = action; 
        submissiondata['idxname'] = idxname;
        $.ajax({
          type: "POST",
          url: url,
          data: submissiondata, 
          success: (e2) => {
            if(e2.added === 1){
                let new_idx_name = e2.name
                e.setAttribute('data_idxname', new_idx_name); 
                e.classList.remove('noIndex'); 
                e.classList.add('hasIndex'); 
                e.classList.remove('bg-red-200'); 
                e.classList.add('bg-green-200'); 
            }else if (e2.removed === 1){
                e.classList.remove('hasIndex'); 
                e.classList.add('noIndex'); 
                e.classList.remove('bg-green-200'); 
                e.classList.add('bg-red-200'); 
                e.removeAttribute('data_idxname'); 
            }
          }
        })

    }); 
}