function showcounters(on){
  var target = document.getElementById(on+'Counter');
  var urlCall = 'AJAX/homepageCounters.php?by='+on;
  fetch(urlCall)
  .then(response => response.json())
  .then(data => {
    for( var k in data ) {
      var mainbox = document.createElement('div');
      mainbox.classList.add('w-7/8', 'justify-center');
      var subbox = document.createElement('div');
      subbox.classList.add('bg-gray-900', 'text-gray-100', 'rounded-md');
      var keybox = document.createElement('div');
      keybox.classList.add('flex', 'uppercase', 'font-bold', 'w-full', 'justify-center');
      keybox.innerHTML = k;
      var valbox = document.createElement('div');
      valbox.classList.add('flex', 'space-x-2', 'justify-center', 'w-full', 'text-lg');
      valbox.innerHTML = Number(data[k]).toLocaleString('en');
      subbox.appendChild(keybox);
      subbox.appendChild(valbox);
      mainbox.appendChild(subbox);
      target.appendChild(mainbox)
    }
  });
}
