function labelSelected(){
  console.log('selected');
  var source = event.source||event.target;
  var target = document.getElementById('nodeProperties').getElementsByClassName('subbox')[0];
  //console.log(source.value);
  fetch('AJAX/nodes.php?action=properties&on='+source.value)
    .then(response => response.json())
    .then(data => {
      console.log(data);
      var form = document.createElement('form');
      for(var i = 0; i < data.length; i++){
        var keyData = data[i];
        var keyDB = keyData[0];
        var keyHR = keyData[1];
        if (!(keyHR)){
          keyHR = keyDB;
        }
        var input = document.createElement('input');
        input.setAttribute('name', keyDB);
        var label = document.createElement('label');
        label.setAttribute('for', keyDB);
        var labelTex = document.createTextNode(keyHR);
        label.appendChild(labelTex);
        form.appendChild(label);
        form.appendChild(input);
      }
      target.innerHTML = '';
      target.appendChild(form);
    });
}

function searchInit() {
  var primaryDivTarget = document.getElementById('nodeTypes').getElementsByClassName('subbox')[0];
  fetch('AJAX/nodes.php?action=labels')
    .then(response => response.json())
    .then(data => {
      for (var i = 0; i < data.length; i++){
        var labelData = data[i];
        var labelDB = labelData[0];
        var labelHR = labelData[1];
        if (!(labelHR)){
          labelHR = labelDB;
        }
        var input = document.createElement('input');
        input.setAttribute('type', 'radio');
        input.setAttribute('name', 'nodeLabel');
        input.setAttribute('value', labelDB);
        input.classList.add('form-check-input', 'appearance-none', 'rounded-full', 'h-4', 'w-4', 'border', 'border-gray-300', 'bg-white', 'checked:bg-blue-600', 'checked:border-blue-600', 'focus:outline-none', 'transition', 'duration-200', 'mt-1', 'align-top', 'bg-no-repeat', 'bg-center', 'bg-contain', 'float-left', 'mr-2', 'cursor-pointer');
        var label = document.createElement('p');
        var labelTex = document.createTextNode(labelHR);
        label.appendChild(labelTex);
        var labelGroup = document.createElement('div');
        labelGroup.classList.add('form-check', 'border-2');
        labelGroup.appendChild(input);
        labelGroup.appendChild(label);
        input.addEventListener('click', function(){
          labelSelected();
          }
        );
        primaryDivTarget.appendChild(labelGroup);
      }
    });
}
