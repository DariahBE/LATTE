function dropnodefromdb(nodeName){
    getDisposableToken()
    .then(token => {
        let formData = new FormData();
        formData.append('token', token);
        formData.append('operation', 'noderemoval');
        formData.append('nodename', nodeName);
        return fetch('ajax/patch.php', {
            method: 'POST',
            body: formData
        });
    })
    .catch(error => {
        console.error(error); // Handle errors from getDisposableToken
    });

}

    function patchUUID() {
        getDisposableToken()
            .then(token => {
                let formData = new FormData();
                formData.append('token', token);
                formData.append('operation', 'fixuuid');
                return fetch('ajax/patch.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(response => {
                    console.log('UUID patched successfully:', response);
                });
            })
            .catch(error => {
                console.error(error); // Handle errors from getDisposableToken
            });
    }
    

function getDisposableToken() {
    return fetch('/AJAX/getdisposabletoken.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error fetching token: ' + response.statusText);
            }
            return response.json();
        });
}
