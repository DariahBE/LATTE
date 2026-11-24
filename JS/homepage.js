function showcounters(on){
  const target = document.getElementById(on + 'Counter');
  if (!target) return;
  const urlCall = 'AJAX/homepageCounters.php?by=' + encodeURIComponent(on);
  fetch(urlCall)
    .then(response => response.json())
    .then(data => {
      target.innerHTML = '';
      for (const k in data) {
        const card = document.createElement('div');
        card.classList.add('bg-white', 'border', 'border-slate-200', 'rounded-xl', 'p-4', 'shadow-sm', 'text-center', 'hover:shadow-md', 'transition-shadow');
        
        const keybox = document.createElement('div');
        keybox.classList.add('text-xs', 'font-semibold', 'uppercase', 'tracking-wider', 'text-slate-500', 'mb-1');
        keybox.textContent = k;
        
        const valbox = document.createElement('div');
        valbox.classList.add('text-2xl', 'font-bold', 'text-indigo-600');
        valbox.textContent = Number(data[k]).toLocaleString('en');
        
        card.appendChild(keybox);
        card.appendChild(valbox);
        target.appendChild(card);
      }
    })
    .catch(error => {
      console.error('Error fetching counters for ' + on + ':', error);
    });
}
