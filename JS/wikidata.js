
/**
 *  A simple class to collect wikidata information based on a provided wikidata QID: !
 *      
 *      INITIATE: 
 *        a = new wikibaseEntry('Q661619', wdProperties);
 *          //first argument is a valid Q-identifier: 
 *          //second argument is a dict of usersettings. 
 *      GETTING WIKIDATA DATA: 
 *        a.getWikidata(); 
 */
class wikibaseEntry {
  Qid;                    //What is the provided Qid (used for items) || or the string to lookup when searchMode === "str"
  rawData;                //data returned by the wikidata server. 
  usersettings;           //returned by the server: connected to user account AND/OR cookies AND/OR defaultsettings. 
  valid;                  //is the ID valid:
  searchMode;             //Is the query looking for a QID or a string based match. 
  classifier;             //Loops over all Q-ids and classifies them: Disambiguation Page; Place; Person; other. 
  Qindicators;

  //vallidate the Input dependent on the key ==> QID 
  constructor(inputvariable, settings, by='qid') {
    //id should be validated first: does it start with Q followed by int. 
    this.usersettings = settings; 
    this.Qindicators = {
      'PERSON': [],
      'PLACE': []
    };
    if(by === 'qid'){
      const qvalidator = new RegExp('Q[0-9]+$'); 
      if(qvalidator.test(inputvariable)){
        this.searchMode = 'qid';
        this.Qid = inputvariable; 
      }else{
        this.valid = false; 
        throw new Error('The provided parameter is not a valid Q-ID: (SEE https://www.wikidata.org/wiki/Q43649390).');
      }
    }else if(by === 'str'){
      //matching on string based search: 
      this.searchMode = 'str';
      this.Qid = inputvariable; 
    }else{
      throw new Error('SearchMode is not valid; choose either "str" or "qid"');
    }
  }

  sparqlStuff(url){
    var relatedQs = []; 
    fetch(url)
    .then(response => response.text())
    .then(str => $.parseXML(str))
    .then(data => {
      console.log(data); 
      var tags = data.getElementsByClassName('binding'); 
      console.log(tags);
    });
    // Something wrong with this shit. Ill do it on monday
    return relatedQs;
  }


  getWikidata(){
    console.log(this.searchMode);
    let url = '';
    if (this.searchMode === 'qid'){
      url = wdk.getEntities({
        ids: [ this.Qid ],
        format: 'json', // defaults to json
        redirections: false // defaults to true
      }); 
    }else if(this.searchMode === 'str'){
      var wikiLangLinks = Object.keys(wdProperties['stringmatchWikipediaTitles']);
      var wikiLangLinksForSites = wikiLangLinks.join('|'); 
      /**
       * the SDK keeps setting normalized=true with no way to override it from within the object. 
       * BUT: if normalization is enabled; then you can only look in one site!
      */
      url = wdk.getEntitiesFromSitelinks({
        titles: [ this.Qid ],
        sites: wikiLangLinksForSites, 
        normalized: false,
        format: 'json',
        redirections: false // defaults to true
      }); 
      //strip normalization out of the url; IF wikiLangLinks.length > 1:
      if(wikiLangLinks.length > 1){
        url = url.replace('&normalize=true',''); 
      }
  }else{
    console.log('not valid');
  }
  console.log(url);
    fetch(url)
      .then(response => response.json())
      // Turns the response in an array of simplified entities
      .then(response => {if(response['error']){this.valid=false; return null;}else{return response;}})
      .then(wdk.parse.wd.entities)
      .then(entities => this.rawData = entities  );
  }

  classify(){
    this.classifier = {
      'PPL' : [], 
      'PLC' : [], 
      'DAP' : [], 
      'OTH' : [],
      'garbage': []
    };
    const arr = Object.keys(this.rawData);
    console.log(arr); 
    arr.forEach(element => {
      this.determineTypeOfEntity(element); 
    });
    
  }

  determineTypeOfEntity(entity){
    //function should only be called as part of the disambiguation process.
    //determine if a returned Q-id has a P31 key, parse that content!. 
    //P31 results: 
      //    Q5    = Human
      //    Qxx   = DisambiguationPage
      //    Qxx   = Place
    // entity is negative INT ?=  title did not return in a given language > error!
    if (entity.startsWith('-')){          //not found
      array.push(this.classifier['garbage'], entity); 
      return;
    }
    let ett = this.rawData[entity]; 
    if(ett.claims.P31 === "Q4167410"){    //disambiguation page. 
      array.push(this.classifier['DAP'], entity); 
      return; 
    }
    //use the sparql engine to query the Q-item and see what it returns on the P31 tag: 
    const primarySparqlCheck = "https://query.wikidata.org/sparql?query=PREFIX+gas:+%3Chttp:%2F%2Fwww.bigdata.com%2Frdf%2Fgas%23%3E%0A%0ASELECT+%3Fitem+%3FitemLabel+%3FlinkTo+%7B%0A++SERVICE+gas:service+%7B%0A++++gas:program+gas:gasClass+%22com.bigdata.rdf.graph.analytics.SSSP%22+;%0A++++++++++++++++gas:in+wd:"+entity+"+;%0A++++++++++++++++gas:traversalDirection+%22Forward%22+;%0A++++++++++++++++gas:out+%3Fitem+;%0A++++++++++++++++gas:out1+%3Fdepth+;%0A++++++++++++++++gas:maxIterations+5+;%0A++++++++++++++++gas:linkType+wdt:P31+.%0A++%7D%0A++OPTIONAL+%7B+%3Fitem+wdt:P31+%3FlinkTo+%7D%0A++SERVICE+wikibase:label+%7Bbd:serviceParam+wikibase:language+%22en%22+%7D%0A%7D"; 
    this.sparqlStuff(primarySparqlCheck); 
  }

  /**
   *  Some decisions to keep in mind: 
   *    Wikidata returns a place property: response['claims']['P625'] ==> WGS84 coordinating system !!
   *    
   */ 

  //////////////////////////////////////////////////////////////////////////////////////////////////////
  //////////////////////////////////////////////////////////////////////////////////////////////////////
  //////////////////////////////////////////////////////////////////////////////////////////////////////
  /**Display all returned items; some remarks: 
   *  when using str-mode;
   *        - -1 is returned as Q-key if a certain language does not hold an articletitle for a given string.
   *        - Two or more distinct Q-IDs can be used to name a unique entity: (Paris => City or Person???)
   *            - What is a good disambiguation strategy??!!
   *            - If CLAIMS contains a key P31 AND if that key holds a value: 
   *                Q5 ==> then the entity is a person!
   *                Qxxxx ==> then the entity is a place!
   */

  displayCoordinateData(lat, long){
    //if claims contain a key 'P625' then there's coordinate Data for the returned XHR call: show it. 

  }





};

/*
//some helper functions outside the class scope: 
convertURLtoQ(url){
    
}
*/