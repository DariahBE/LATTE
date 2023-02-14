
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
  Pindicators;            //Array of used P-tags in the claims-section of an entity. . 

  //vallidate the Input dependent on the key ==> QID 
  constructor(inputvariable, settings, by='qid') {
    //id should be validated first: does it start with Q followed by int. 
    this.usersettings = settings; 
    this.Pindicators = {
      'PERSON': ["P1006","P1038","P1047","P1185","P119","P1196","P1213","P1233","P1290","P1317","P1415","P1422","P157","P1648","P1710","P19","P1907","P1908","P1935","P1938","P1971","P20","P2180","P22","P2421","P2460","P2498","P25","P26","P2732","P2744","P2745","P2753","P2944","P2972","P2973","P2977","P3029","P3150","P3217","P3373","P3448","P3576","P3595","P3909","P40","P4177","P4180","P4193","P4359","P4459","P451","P4602","P4789","P496","P4963","P509","P5563","P569","P570","P5726","P5756","P5756","P5956","P6038","P6059","P6167","P6234","P651","P6829","P6941","P6996","P7023","P7041","P7042","P723","P7902","P7928","P7939","P7941","P8081","P8122","P8130","P8341","P8810","P9058","P947"],
      'PLACE': ["P30","P36","P47","P131","P189","P206","P276","P291","P403","P610","P613","P625","P669","P706","P931","P937","P1302","P1332","P1333","P1334","P1335","P1376","P1589","P2672","P2825","P3018","P3032","P3096","P3137","P3179","P3403","P3470","P3842","P4091","P4388","P4552","P4565","P4647","P4688","P5248","P5607","P5998","P6375","P17","P150","P190","P242","P281","P296","P297","P298","P299","P300","P374","P382","P402","P429","P439","P440","P442","P454","P455","P500","P501","P507","P525","P539","P590","P605","P630","P635","P677","P716","P721","P722","P757","P761","P764","P771","P772","P773","P774","P775","P776","P777","P778","P779","P782","P804","P806","P809","P814","P821","P843","P882","P901","P909","P939","P948","P954","P964","P981","P984","P988","P1010","P1067","P1077","P1115","P1116","P1140","P1168","P1172","P1188","P1203","P1217","P1276","P1281","P1282","P1305","P1311","P1336","P1370","P1380","P1381","P1383","P1388","P1397","P1398","P1400","P1404","P1456","P1459","P1460","P1481","P1566","P1584","P1585","P1602","P1621","P1653","P1667","P1684","P1699","P1717","P1732","P1792","P1837","P1841","P1848","P1850","P1854","P1866","P1871","P1879","P1886","P1887","P1894","P1920","P1936","P1937","P1943","P1944","P1945","P1958","P1976","P2025","P2081","P2082","P2099","P2100","P2123","P2186","P2258","P2270","P2290","P2326","P2467","P2468","P2473","P2477","P2487","P2491","P2496","P2497","P2503","P2504","P2505","P2506","P2516","P2520","P2525","P2526","P2561","P2564","P2584","P2585","P2586","P2588","P2595","P2618","P2621","P2633","P2659","P2672","P2673","P2674","P2762","P2763","P2783","P2787","P2788","P2815","P2817","P2856","P2863","P2866","P2867","P2887","P2917","P2927","P2929","P2956","P2971","P2980","P2981","P2982","P2983","P3009","P3012","P3024","P3059","P3067","P3104","P3108","P3109","P3118","P3119","P3120","P3182","P3197","P3198","P3202","P3209","P3211","P3223","P3227","P3230","P3238","P3256","P3257","P3296","P3304","P3309","P3326","P3335","P3353","P3371","P3394","P3396","P3401","P3407","P3412","P3419","P3422","P3423","P3425","P3426","P3472","P3481","P3498","P3503","P3507","P3513","P3514","P3515","P3516","P3517","P3555","P3562","P3563","P3572","P3580","P3601","P3609","P3613","P3615","P3616","P3626","P3627","P3628","P3633","P3635","P3639","P3676","P3707","P3714","P3723","P3727","P3728","P3731","P3735","P3749","P3758","P3759","P3770","P3806","P3809","P3810","P3813","P3824","P3850","P3856","P3863","P3866","P3896","P3907","P3920","P3922","P3972","P3974","P3988","P3990","P3992","P3993","P4001","P4005","P4007","P4009","P4014","P4029","P4038","P4046","P4055","P4059","P4075","P4083","P4088","P4091","P4093","P4094","P4098","P4117","P4118","P4119","P4133","P4136","P4141","P4142","P4143","P4146","P4154","P4170","P4171","P4172","P4182","P4219","P4227","P4244","P4245","P4246","P4249","P4266","P4291","P4328","P4334","P4335","P4340","P4346","P4352","P4356","P4358","P4388","P4401","P4423","P4528","P4533","P4535","P4591","P4595","P4641","P4658","P4672","P4689","P4694","P4697","P4702","P4708","P4711","P4755","P4762","P4777","P4792","P4800","P4803","P4812","P4820","P4856","P4881","P5010","P5011","P5020","P5050","P5140","P5141","P5180","P5207","P5208","P5215","P5288","P5289","P5294","P5388","P5400","P5464","P5515","P5535","P5598","P5599","P5601","P5611","P5633","P5634","P5652","P5696","P5746","P5757","P5758","P5759","P5761","P5763","P5764","P5782","P5818","P5904","P5946","P5965","P6006","P6017","P6082","P6120","P6144","P6148","P6155","P6192","P6230","P6233","P6244","P6265","P6340","P200","P201","P205","P403","P469","P761","P884","P885","P974","P1200","P1404","P1717","P2516","P2584","P2856","P3006","P3119","P3326","P3394","P3707","P3866","P3871","P4002","P4190","P4202","P4279","P4511","P4528","P4568","P4614","P4661","P4792","P5079","P6148"], 
      'EVENT': ["P585","P710","P1343","P625","P582","P580","P1299","P1120","P1339","P1446","P1478","P1561","P1590","P2630","P3081","P3082","P11157"]
    };
    //P625 is ambiguous for both Place and Event parameters!; however, it is valuable to show anyway
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


  async getWikidata(){
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
      /*
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
  //console.log(url);
    return await fetch(url)
      .then(response => response.json())
      // Turns the response in an array of simplified entities
      .then(response => {if(response['error']){this.valid=false; return null;}else{return response;}})
      .then(wdk.parse.wd.entities)
      .then(entities => this.rawData = entities);
  }

  classify(){
    this.classifier = {
      'PPL' : [], 
      'PLC' : [], 
      'DAP' : [], 
      'EVT' : [],
      'garbage': []
    };
    const arr = Object.keys(this.rawData);
    arr.forEach(element => {
      let ett = this.rawData[element]; 
      // entity is negative INT ?=  title did not return in a given language > error!
      if (element.startsWith('-')){                 //not found
        this.classifier['garbage'].push(element); 
      //determine if a returned Q-id has a P31 key, parse that content!. 
      //P31 results: 
      //    Q5          = Human
      //    Q4167410    = DisambiguationPage
      //    Qxx   = Place ==> no real page, a whole bunch of indicators. See this.Pindicators.
      }else if(ett.claims.P31.includes("Q4167410")){      //disambiguation page. 
        this.classifier['DAP'].push(element); 
      }else if(ett.claims.P31.includes("Q5")){      //human.
        this.classifier['PPL'].push(element); 
      }else{
        // match against P-tags to see highest probability: 
        //Basic function: 
        function inCommon(variable, constant){
          var commonCounter = 0; 
          for(let iv = 0; iv < variable.length; iv++){
            if (constant.includes(variable[iv])){
              commonCounter++; 
            }
          }
          return commonCounter/variable.length;
        }   // end of function:
        var scoreCard = {
          'PPL': inCommon(Object.keys(ett.claims), this.Pindicators.PERSON), 
          'PLC': inCommon(Object.keys(ett.claims), this.Pindicators.PLACE),
          'EVT': inCommon(Object.keys(ett.claims), this.Pindicators.EVENT)
        }
        var highestScore = Object.keys(scoreCard).reduce((a, b) => scoreCard[a] > scoreCard[b] ? a : b);
        this.classifier[highestScore].push(element); 
      }
    });
    console.log(this.classifier);
  }

  renderEntities(qid){
    //with all the entities categorized by the classify() call:
    //show them in the DOM. Use user preferences and highest probability to display ets:
    //in STR mode: 
    //    cases can go from 0 to multiple entities!
    //in Q-mode:
    //    One entity only; 
    if(this.searchMode === 'qid'){

      console.log("Start Render: ");
      console.log(this.rawData); 
      console.log("RenderModel: "); 
      console.log(this.usersettings['shownProperties']);
      console.log(Object.keys(this.usersettings['shownProperties']));
      console.log('startiter', Object.keys(this.rawData[qid].claims));
      Object.keys(this.rawData[qid].claims).forEach(e => {

        if (Object.keys(this.usersettings['shownProperties']).includes(e)){
          var wikidata_response = this.rawData[qid].claims[e]; 
          var userSelected = this.usersettings['shownProperties'][e];
          console.log('Matching property: ', e);
          console.log(wikidata_response, userSelected);
        }
        
      } );
      console.log('endsiter');
      
    }
    
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
   *                Q5 ==> then the entity is an attested person! (HUMAN)
   */



  /**
   * Below are the methods used to display specific datatypes. 
   * Settings are controlled by the user!
   * We distinguish: 
   *    - Coordinate data => show as a map
   *    - Image data => show an image
   *    - String => a simple string based value
   *    - URI => if present an uri to the object referenced by the P property!
   */ 
  displayCoordinateData(lat, long){
    //if claims contain a key 'P625' then there's coordinate Data for the returned XHR call: show it. 
    //DO NOT use wikimedia tileserver: usage policy does not support intended use. In stead use OSM:
    //https://operations.osmfoundation.org/policies/tiles/
    //response['claims']['P625'] ==> WGS84 coordinating system !!
  }

  displayImageData(label, image){

  }

  displayStringData(label, value){

  }

  displayURI (parent, identifierOfEntity){
    // get the P1630Property of the element: Needs the parent element
    var urlForPatternRequest = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids="+parent+"&props=claims&format=json"
    // combine that with the identifierOfEntity
    fetch(urlForPatternRequest)
      .then(response => response.json())
      // Turns the response in an array of simplified entities
      .then(console.log(response));
}





};
