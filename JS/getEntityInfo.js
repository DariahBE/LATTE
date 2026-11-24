function getInfoFromBackend(url){
  return fetch(url).then(response => response.json());
}
