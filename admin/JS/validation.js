function dropnodefromdb(nodeName){
    getDisposableToken()
    .then(token => {
        console.log(token);
        let url = 'ajax/patch.php?operation=noderemoval&nodename='+nodeName;
        $.ajax({
            type: "POST",
            url: url,
            data: {token: token}
        });
    })
    .catch(error => {
        console.error(error); // Handle errors from getDisposableToken
    });

}

    function patchUUID() {
        getDisposableToken()
            .then(token => {
                let url = 'ajax/patch.php?operation=fixuuid';
                $.ajax({
                    type: "POST",
                    url: url,
                    data: { token: token },
                    success: function(response) {
                        console.log('UUID patched successfully:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error patching UUID:', status, error);
                    }
                });
            })
            .catch(error => {
                console.error(error); // Handle errors from getDisposableToken
            });
    }
    

function getDisposableToken() {
    return new Promise((resolve, reject) => {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/AJAX/getdisposabletoken.php', true);
        
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    resolve(xhr.responseText);
                } else {
                    reject('Error fetching token: ' + xhr.statusText); // Reject the promise
                }
            }
        };
        xhr.send(); // Send the request
    });
}
