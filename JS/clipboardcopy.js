
function clippy(idOfElement,idOfOkay){
  var source = document.getElementById(idOfElement);
  let target = document.createElement('textarea');
  target.id = 'temp_copy_elem';
  target.style.height = 0;
  document.body.appendChild(target);
  target.value = source.innerText;
  let selector = document.querySelector('#temp_copy_elem');
  selector.select();
  //BUG (low)! execCommand is deprecated, but there's no alternative yet.
  // You can consider the clipboard API, but this requires https!!! which when deployed
  // locally will complicate setup for endusers. 
  document.execCommand('copy');
  document.body.removeChild(target);
  if(idOfOkay){
    document.getElementById(idOfOkay).innerText = "URI has been copied.";
    toggle(idOfElement, idOfOkay);
    setTimeout(function() {
      document.getElementById(idOfOkay).innerText = "";
      toggle(idOfOkay,idOfElement);
    }, 1500);
  }
}

function toggle(hide, show){
  document.getElementById(hide).classList.add('hidden');
  document.getElementById(show).classList.remove('hidden');
}
