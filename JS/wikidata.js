
/**
 *  A simple class to collect wikidata information based on a provided wikidata QID: !
 */
class wikibaseEntry {
  Qid;                    //What is the provided Qid (used for items)
  rawData;                //data returned by the wikidata server. 
  defaultLanguage; 
  fallbackLanguage;

    //languagePolicy: 
  /**
   *    Default: Cookie provided language
   *    Fallback: Englih
   *    IfAllElseFails: First provided item in propertylist!
  */
  helper_setLanguages(){
    this.defaultLanguage = '';        //read from cookie if exists; otherwise script uses the fallbackLanguage. 
    this.fallbackLanguage = 'en'; 
  }

  //vallidate the ID
  constructor(id) {
    //id should be validated first: does it start with Q followed by int. 
    const qvalidator = new RegExp('Q[0-9]+$'); 
    if(qvalidator.test(id)){
      this.Qid = id; 
      this.helper_setLanguages(); 
    }else{
      throw new Error('The provided parameter is not a valid Q-ID: (SEE https://www.wikidata.org/wiki/Q43649390).');
    }
  }


  getWikidata(){
    const url = wdk.getEntities({
      ids: [ this.Qid ],
      props: ['sitelinks'],
      format: 'json', // defaults to json
      redirections: false // defaults to true
    }); 
    fetch(url)
      .then(response => response.json())
      // Turns the response in an array of simplified entities
      .then(wbk.parse.wb.entities)
      .then(entities => this.rawData = entities  );
  }

  displayCoordinateData(lat, long){
    //if claims contain a key 'P625' then there's coordinate Data for the returned XHR call: show it. 

  }

  /**
   *  Some decisions to keep in mind: 
   *    Wikidata returns a place property: response['claims']['P625'] ==> WGS84 coordinating system !!
   *    
   */ 

}

