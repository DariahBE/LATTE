function dropnodefromdb(nodeName, token){
    let url = 'ajax/patch.php?operation=noderemoval&token='+token+'&nodename='+nodeName;
    alert(url);
}

function patchUUID(token){
    let url = 'ajax/patch.php?operation=fixuuid';
    $.ajax({
        type: "POST",
        url: url,
        data: {token: token}
    });
}