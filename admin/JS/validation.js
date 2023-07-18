function dropnodefromdb(nodeName, token){
    let url = 'ajax/patch.php?operation=noderemoval&token='+token+'&nodename='+nodeName;
    alert(url);
}