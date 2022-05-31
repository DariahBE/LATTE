
function detectLanguage(options){
  return new Promise(function(resolve, reject){
    var language = options['ISO_code'];
    var nodeid = options['nodeid'];
    if (!(language)){
    const param = {
      node: nodeid
    };
    /*const options = {
        method: 'POST',
        headers: {"Content-type": "application/x-www-form-urlencoded; charset=UTF-8"},
        body: JSON.stringify( param )
    };*/
    var getparam = jQuery.param(param);
    $.ajax({
      method:"GET",
      headers: {"Content-type": "application/x-www-form-urlencoded; charset=UTF-8"},
      url:"/AJAX/getLang_async.php?"+getparam,
      success: function(data) {
        resolve(data) // Resolve promise and go to then()
      },
      error:  function(err) {
        reject(err) // Reject the promise and go to catch()
      }
    });
  }

})
  //_callback();
}


function displayLanguage(langdetect){
  $("#detectedLanguage").text(langdetect['language']);
  $("#detectedLanguageCode").text(langdetect['languageCode']);
  $("#detectedLanguageCertainty").text(langdetect['certainty']);
  languageOptions.ISO_code = langdetect['languageCode'];
}
