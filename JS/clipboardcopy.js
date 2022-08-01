function clippy(idOfElement){
  var source = document.getElementById(idOfElement);
  let target = document.createElement('textarea');
  target.id = 'temp_copy_elem';
  target.style.height = 0;
  document.body.appendChild(target);
  textargettarea.value = document.getElementById("uri").innerText;
  let selector = document.querySelector('#temp_copy_elem');
  selector.select();
  document.execCommand('copy');
  document.body.removeChild(target);
  document.getElementById("temp_copy_ok").innerText = "URI has been copied.";
  setTimeout(function() {
    document.getElementById("temp_copy_ok").innerText = "";
  }, 1500);
}
