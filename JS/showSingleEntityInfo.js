function loadSigmaModules(){

}

function generateNodeOverlayWindow(e){
  var nodes = JSON.parse(e.target.parentElement.getAttribute('data-retrievednodes'));
  var edges = JSON.parse(e.target.parentElement.getAttribute('data-retrievededges'));
  console.log(nodes, edges);

  function toggleOverlay(){
    $("#setNodeDetailOverlay").toggleClass('shownOverlay');
    $("#setNodeDetailOverlay").toggleClass('hiddenOverlay');

  }

  function exitOverlay(){
    console.log('closing');
    $("#setNodeDetailOverlay").html('');
    toggleOverlay();
  }


  function generateOverlay(){
    //window where you interact with the graph projection
    $window = document.createElement('div');
    $window.classList.add('overlay', 'nodeInfoBox');

    //muted background
    $background = document.createElement('div');
    $background.classList.add('backgroundMute');
    $background.setAttribute('id', 'nodeOverlayContainer');
    $background.addEventListener('click', function(){
      exitOverlay();
    });

    $background.appendChild($window);
    //DOM OPerations to show the overlay:
    $docTarget = $("#setNodeDetailOverlay");
    $docTarget.html('');
    $docTarget[0].appendChild($background);
    toggleOverlay();
  }

  generateOverlay();
}

$(document).ready(function(){
  loadSigmaModules();
});
