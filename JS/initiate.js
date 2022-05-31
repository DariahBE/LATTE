$( document ).ready(function() {
  detectLanguage(languageOptions).then(function(result){
    displayLanguage(result);
    getEntities(languageOptions);
    //displayEntities(foundEntities);
  });          //extracts the language code.
//with the languagecode; extract entities:
})
