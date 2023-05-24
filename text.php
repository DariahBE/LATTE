<?php
//include_once('includes/getnode.inc.php');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/wikidata_user_prefs.inc.php');
include_once(ROOT_DIR.'/includes/multibyte_iter.inc.php');
include_once(ROOT_DIR.'/includes/annotation.inc.php');
include_once(ROOT_DIR.'/includes/navbar.inc.php');
if(isset($_GET['texid'])){
  $propId = $_GET['texid'];
  $nodeType = TEXNODE;
  $propKey = helper_extractPrimary($nodeType);
  //$propKey = PRIMARIES[$nodeType];
  //cast the propID to int if type is set:
  $typeOfId = NODEMODEL[$nodeType][$propKey][1];
  if($typeOfId === "int"){
    $propId = (int)$propId;
  }
}else{
  header('Location: /error.php?type=textmissing');
  die();
}

$user = new User($client);
$annotations = new Annotation($client);
$wikidata = new Wikidata_user($client);

$user_uuid = $user->checkSession();


$wikidata->buildPreferences();
$node = new Node($client);
$text = $node->matchSingleNode($nodeType, $propKey, $propId);
if(!boolval($text) or !array_key_exists('coreID', $text)){
  header('Location: /error.php?type=text&id='.$propId);
  die();
}
$nodeId = $text['coreID'];
$neoId = $text['neoID'];  
$existingAnnotation = $annotations->getExistingAnnotationsInText($neoId, $user_uuid);
//$relations = $node->getEdges($nodeId);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME ?></title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <script src="/JS/initiate.js"></script>
    <script src="/JS/getLang.js"></script>
    <script src="/JS/getEntities.js"></script>
    <!-- <script src="/JS/setPositions.js"></script> -->
    <script src="/JS/getEntityInfo.js"></script>
    <script src="/JS/showSingleEntityInfo.js"></script>
    <script src="/JS/rangy/rangy-core.js"></script>
    <script src="/JS/selectInText.js"></script>
    <script src="/JS/showStoredAnnotations.js"></script>
    <script src="/JS/interactWithEntities.js"></script>
    <!-- wikidata SDK and custom code! SDK docs: https://github.com/maxlath/wikibase-sdk-->
    <script src="/JS/wikidata_SDK/wikibase-sdk.js"></script>
    <script src="/JS/wikidata_SDK/wikidata-sdk.js"></script>
    <script src="/JS/wikidata.js"></script>
    <!-- extra script for wikidata content: -->
    <script src="/JS/caroussel.js"></script>
    <script src="/JS/makeMap.js"></script>
    <script src="/JS/leaflet/leaflet.js"></script>
    <script src="/JS/wikidata_prompt.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="/CSS/leaflet/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-neutral-200 w-full">
  <?php
    $navbar = new Navbar(); 
    echo $navbar->nav;  
  ?>

    <div class=" 2xl:w-1/2 xl:w-2/3 items-center m-auto"> 
    <!-- content-->

<div class="top ">
  <div id='normalizationDialogue' class="w-full">
    <h3 class='text-xl'>Normalization Options: </h3>
    <p>Normalization improves the pickup of entities. When enabled the Named entity returned by the NER-tool is modified by removing a list of specific characters.</p>
    <div id='normalizationOptions'>
      <div class="flex flex-initialize">
        <label for="normalization_On_Off" class="relative flex justify-between items-center p-2">
          Enable Normalization:
        </label>
          <input type="checkbox" name="normalization_On_Off" class="p-2 border-2 border-black border-solid rounded-md" />
      </div>
      <div>
        <p>Provide a comma (,) separated list of symbols to be normalized: </p>
        <label for="normalization_list">Normalize these symbols: </label>
        <input type="text" id="normalizationList" name="normalization_list" class="p-2 border-2 border-black border-solid rounded-md">
      </div>
    </div>
    <br>

  </div>
  <div id="explorationDialogue" class="w-full py-4 my-4">
    <h3 class="text-xl">Node Exploration: </h3>
    <!-- automatic exploration of the retrieved entities-->
    <label for="autoexplore">Fetch recognized entities: </label>
    <input type="checkbox" name="autoexplore" value="">
  </div>
</div>

<div class="main flex flex-row py-4 my-4">
  <div class="left float-left w-full m-2 p-2" id="leftMainPanel">
  <h3 class="text-xl">Text: </h3>
    <div class="subbox leftsubbox" >
      <div class="flex h-12" id="exportBox">
        <a class="object-contain h-10" href="/export.php?mode=xml&neoid=<?php echo (int)$neoId?>">
          <img class="object-contain h-10 " src='/images/xml-export.png'/>
        </a>
        <a class="object-contain h-10" href="/export.php?mode=json&neoid=<?php echo (int)$neoId?>">
          <img class="object-contain h-10" src='/images/json-export.png'/>
        </a>
      </div>
      <div id="textcontent">
      <?php
        $textString = $text['data'][0]->first()['node']['properties'][TEXNODETEXT];
        $textLanguage = isset($text['data'][0]->first()['node']['properties']['language']) ? $text['data']['properties']['language']: False;
        $i = 0;
        foreach(new MbStrIterator($textString) as $c) {
          echo "<span class='ltr' data-itercounter=$i>".nl2br($c)."</span>";
          $i++;
        }
      ?>

      </div>
    </div>
    <script>
      var coreNodes = <?php echo json_encode(array_keys(CORENODES)); ?>;
      var languageOptions = {
        'text': <?php echo json_encode($textString)?>,
        'ISO_code': <?php echo json_encode($textLanguage)?>,
        'textid': <?php echo json_encode((int)$propId)?>,
        'nodeid': <?php echo json_encode((int)$neoId)?>
      };
      var wdProperties = <?php echo json_encode($wikidata->makeSettingsDictionary()); ?>;
     // var wikidataIndication = <?php //echo json_encode($wikidata->labelIndicator()); ?>;

    </script>
    <style>
      <?php
        //load style settings from config fyle, parse them as inline CSS:

        helper_parseEntityStyle();
      ?>
    </style>
  </div>
  <div class="right float-right" id="rightMainPanel">
      <div class="meta" id="topmeta">
        <!--controlling options for WD string-lookups-->
        <div id='wdoptionsblock'>
          <p class='font-bold'>entity lookup options:</p>
          <select id='wdlookuplanguage'></select>
          <br>
          <input name='returnConstraint' type='checkbox' id='returnSameAsLookup'></input>
          <label for='returnConstraint'>Limit results to lookuplanguage</label>
          <br>
          <input name='lookupConstraint' type='checkbox' id='strictLookup'></input>
          <label for='lookupConstraint'>Use language fallback</label>
          
        </div>
        <div class="language">
          <p><span class='font-bold key'>Language ISO: </span><span class='value italic' id='detectedLanguageCode'></span></p>
          <p><span class='font-bold key'>Language: </span><span class='value italic' id='detectedLanguage'></span></p>
          <p><span class='font-bold key'>Certainty: </span><span class='value italic' id='detectedLanguageCertainty'></span></p>
        </div>
        <div class="options" id="entityMatchOptions">
          <div class="hideMatches">
            <input onclick="hideUnhideEntities()" id='hideUnhideEntities' type="checkbox" name="hideMatchingEntities" value=true>
            <label for="hideMatchingEntities">Hide <span id='overlapcount'></span>annotated entities(s)</label>
          </div>
        </div>
        <div class="entities">
          <p><span class='font-bold key'>Nr. of entities: </span><span class='value italic' id='amountOfEntities'></span></p>
          <p><span class='font-bold key'>Used model: </span><span class='value italic' id='usedEntityModel'></span></p>
        </div>
      </div>
      <div class="entities">
        <div class="report" id="entitycontainer">

        </div>
        <div class="analyse" id="specificEntityDetails">

        </div>
      </div>
  </div>
  <!--<div class="extended" id="rightExtensionPanel">
    <div class="base">
      <! -- What is shown by default in the right extension panel. - ->

    </div>
    <div class="full">
      <! -- Extra slideOut panel- ->

    </div>
  </div> -->
</div>
<div id="slideover-container" class="right-0 w-1/2 h-full fixed top-0 invisible z-50">
  <!--<div id="slideover-bg" class="w-full h-full duration-500 ease-out transition-all top-0 absolute bg-gray-900 opacity-0"></div>-->
  <div id="slideover" class="w-full bg-white h-full absolute left-0 duration-300 ease-out transition-all translate-x-full overflow-y-scroll overflow-x-hidden">
  <svg onclick='toggleSlide(0)' xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
  </svg>

      <div id="slideoverDynamicContent" class="absolute text-gray-600 top-0 w-full h-full justify-center left-0 m-5 p-5">
        <!-- with xhr data loaded: put the response here!
          this panel serves as the target for showing data in the NEO database as well as wikidata responses. 
      -->
    </div>
  </div>
</div>
<!--<div id='setNodeDetailOverlay' class='hiddenOverlay'></div>-->
  <?php echo "<script> var storedAnnotations = ".json_encode($existingAnnotation)."</script>";
  if(count($existingAnnotation['relations']) > 0){
    echo "<script>visualizeStoredAnnotations();</script>";
  }

  ?>
  <script>
    const ddtarget = document.getElementById('wdlookuplanguage'); 
    //traverse over language options: use en as default: 
    let languages = {
      aa: "Qafár af‎",ab: "Аҧсшәа‎",abs: "bahasa ambon‎",ace: "Acèh‎",ady: "адыгабзэ‎",
      'ady-cyrl': "адыгабзэ‎",aeb: "تونسي/Tûnsî‎",'aeb-arab': "تونسي‎",'aeb-latn': "Tûnsî‎",
      af: "Afrikaans‎",ak: "Akan‎",aln: "Gegë‎",als: "Alemannisch‎",alt: "тÿштÿк алтай тил‎",
      am: "አማርኛ‎",ami: "Pangcah‎",an: "aragonés‎",ang: "Ænglisc‎",anp: "अङ्गिका‎",
      ar: "العربية‎",arc: "ܐܪܡܝܐ‎",arn: "mapudungun‎",arq: "جازايرية‎",ary: "الدارجة‎",
      arz: "مصرى‎",as: "অসমীয়া‎",ase: "American sign language‎",ast: "asturianu‎",
      atj: "Atikamekw‎",av: "авар‎",avk: "Kotava‎",awa: "अवधी‎",ay: "Aymar aru‎",
      az: "azərbaycanca‎",azb: "تۆرکجه‎",ba: "башҡортса‎",ban: "Bali‎",bar: "Boarisch‎",
      'bat-smg': "žemaitėška‎",bbc: "Batak Toba‎",'bbc-latn': "Batak Toba‎",bcc: "جهلسری بلوچی‎",
      bcl: "Bikol Central‎",be: "беларуская‎",'be-tarask': "беларуская (тарашкевіца)‎",
      'be-x-old': "беларуская (тарашкевіца)‎",bg: "български‎",bgn: "روچ کپتین بلوچی‎",bh: "भोजपुरी‎",
      bho: "भोजपुरी‎",bi: "Bislama‎",bjn: "Banjar‎",bm: "bamanankan‎",bn: "বাংলা‎",bo: "བོད་ཡིག‎",
      bpy: "বিষ্ণুপ্রিয়া মণিপুরী‎",bqi: "بختیاری‎",br: "brezhoneg‎",brh: "Bráhuí‎",bs: "bosanski‎",
      btm: "Batak Mandailing‎",bto: "Iriga Bicolano‎",bug: "ᨅᨔ ᨕᨘᨁᨗ‎",bxr: "буряад‎",ca: "català‎",
      'cbk-zam': "Chavacano de Zamboanga‎",cdo: "Mìng-dĕ̤ng-ngṳ̄‎",ce: "нохчийн‎",ceb: "Cebuano‎",
      ch: "Chamoru‎",cho: "Choctaw‎",chr: "ᏣᎳᎩ‎",chy: "Tsetsêhestâhese‎",ckb: "کوردی‎",
      co: "corsu‎",cps: "Capiceño‎",cr: "Nēhiyawēwin / ᓀᐦᐃᔭᐍᐏᐣ‎",crh: "qırımtatarca‎",
      'crh-cyrl': "къырымтатарджа (Кирилл)‎",'crh-latn': "qırımtatarca (Latin)‎",cs: "čeština‎",
      csb: "kaszëbsczi‎",cu: "словѣньскъ / ⰔⰎⰑⰂⰡⰐⰠⰔⰍⰟ‎",cv: "Чӑвашла‎",cy: "Cymraeg‎",
      da: "dansk‎",de: "Deutsch‎",'de-at': "Österreichisches Deutsch‎",'de-ch': "Schweizer Hochdeutsch‎",
      'de-formal': "Deutsch (Sie-Form)‎",din: "Thuɔŋjäŋ‎",diq: "Zazaki‎",dsb: "dolnoserbski‎",
      dtp: "Dusun Bundu-liwan‎",dty: "डोटेली‎",dv: "ދިވެހިބަސް‎",dz: "ཇོང་ཁ‎",ee: "eʋegbe‎",
      egl: "Emiliàn‎",el: "Ελληνικά‎",eml: "emiliàn e rumagnòl‎",en: "English‎",
      'en-ca': "Canadian English‎",'en-gb': "British English‎",eo: "Esperanto‎",es: "español‎",
      'es-419': "español de América Latina‎",'es-formal': "español (formal)‎",et: "eesti‎",eu: "euskara‎",
      ext: "estremeñu‎",fa: "فارسی‎",ff: "Fulfulde‎",fi: "suomi‎",fit: "meänkieli‎",
      'fiu-vro': "Võro‎",fj: "Na Vosa Vakaviti‎",fkv: "kvääni‎",fo: "føroyskt‎",fr: "français‎",
      frc: "français cadien‎",frp: "arpetan‎",frr: "Nordfriisk‎",fur: "furlan‎",fy: "Frysk‎",
      ga: "Gaeilge‎",gag: "Gagauz‎",gan: "贛語‎",'gan-hans': "赣语（简体）‎",'gan-hant': "贛語（繁體）‎",
      gcr: "kriyòl gwiyannen‎",gd: "Gàidhlig‎",gl: "galego‎",glk: "گیلکی‎",gn: "Avañe'ẽ‎",
      gom: "गोंयची कोंकणी / Gõychi Konknni‎",'gom-deva': "गोंयची कोंकणी‎",'gom-latn': "Gõychi Konknni‎",
      gor: "Bahasa Hulontalo‎",got: "𐌲𐌿𐍄𐌹𐍃𐌺‎",grc: "Ἀρχαία ἑλληνικὴ‎",gsw: "Alemannisch‎",gu: "ગુજરાતી‎",
      gv: "Gaelg‎",ha: "Hausa‎",hak: "客家語/Hak-kâ-ngî‎",haw: "Hawaiʻi‎",he: "עברית‎",hi: "हिन्दी‎",
      hif: "Fiji Hindi‎",'hif-latn': "Fiji Hindi‎",hil: "Ilonggo‎",ho: "Hiri Motu‎",hr: "hrvatski‎",
      hrx: "Hunsrik‎",hsb: "hornjoserbsce‎",ht: "Kreyòl ayisyen‎",hu: "magyar‎",
      'hu-formal': "magyar (formal)‎",hy: "հայերեն‎",hyw: "Արեւմտահայերէն‎",hz: "Otsiherero‎",
      ia: "interlingua‎",id: "Bahasa Indonesia‎",ie: "Interlingue‎",ig: "Igbo‎",ii: "ꆇꉙ‎",
      ik: "Iñupiak‎",'ike-cans': "ᐃᓄᒃᑎᑐᑦ‎",'ike-latn': "inuktitut‎",ilo: "Ilokano‎",inh: "ГӀалгӀай‎",
      io: "Ido‎",is: "íslenska‎",it: "italiano‎",iu: "ᐃᓄᒃᑎᑐᑦ/inuktitut‎",ja: "日本語‎",
      jam: "Patois‎",jbo: "la .lojban.‎",jut: "jysk‎",jv: "Jawa‎",ka: "ქართული‎",
      kaa: "Qaraqalpaqsha‎",kab: "Taqbaylit‎",kbd: "Адыгэбзэ‎",'kbd-cyrl': "Адыгэбзэ‎",kbp: "Kabɩyɛ‎",
      kea: "Kabuverdianu‎",kg: "Kongo‎",khw: "کھوار‎",ki: "Gĩkũyũ‎",kiu: "Kırmancki‎",
      kj: "Kwanyama‎",kjp: "ဖၠုံလိက်‎",kk: "қазақша‎",'kk-arab': "قازاقشا (تٴوتە)‏‎",
      'kk-cn': "قازاقشا (جۇنگو)‏‎",'kk-cyrl': "қазақша (кирил)‎",'kk-kz': "қазақша (Қазақстан)‎",
      'kk-latn': "qazaqşa (latın)‎",'kk-tr': "qazaqşa (Türkïya)‎",kl: "kalaallisut‎",km: "ភាសាខ្មែរ‎",
      kn: "ಕನ್ನಡ‎",ko: "한국어‎",'ko-kp': "조선말‎",koi: "Перем Коми‎",kr: "Kanuri‎",
      krc: "къарачай-малкъар‎",kri: "Krio‎",krj: "Kinaray-a‎",krl: "karjal‎",
      ks: "कॉशुर / کٲشُر‎",'ks-arab': "کٲشُر‎",'ks-deva': "कॉशुर‎",ksh: "Ripoarisch‎",ku: "kurdî‎",
      'ku-arab': "كوردي (عەرەبی)‏‎",'ku-latn': "kurdî (latînî)‎",kum: "къумукъ‎",kv: "коми‎",
      kw: "kernowek‎",ky: "Кыргызча‎",la: "Latina‎",lad: "Ladino‎",lb: "Lëtzebuergesch‎",
      lbe: "лакку‎",lez: "лезги‎",lfn: "Lingua Franca Nova‎",lg: "Luganda‎",li: "Limburgs‎",
      lij: "Ligure‎",liv: "Līvõ kēļ‎",lki: "لەکی‎",lld: "Ladin‎",lmo: "lumbaart‎",
      ln: "lingála‎",lo: "ລາວ‎",loz: "Silozi‎",lrc: "لۊری شومالی‎",lt: "lietuvių‎",
      ltg: "latgaļu‎",lus: "Mizo ţawng‎",luz: "لئری دوٙمینی‎",lv: "latviešu‎",lzh: "文言‎",
      lzz: "Lazuri‎",mai: "मैथिली‎",'map-bms': "Basa Banyumasan‎",mdf: "мокшень‎",mg: "Malagasy‎",
      mh: "Ebon‎",mhr: "олык марий‎",mi: "Māori‎",min: "Minangkabau‎",mk: "македонски‎",
      ml: "മലയാളം‎",mn: "монгол‎",mni: "ꯃꯤꯇꯩ ꯂꯣꯟ‎",mnw: "ဘာသာ မန်‎",mo: "молдовеняскэ‎",
      mr: "मराठी‎",mrj: "кырык мары‎",ms: "Bahasa Melayu‎",mt: "Malti‎",mus: "Mvskoke‎",
      mwl: "Mirandés‎",my: "မြန်မာဘာသာ‎",myv: "эрзянь‎",mzn: "مازِرونی‎",na: "Dorerin Naoero‎",
      nah: "Nāhuatl‎",nan: "Bân-lâm-gú‎",nap: "Napulitano‎",nb: "norsk bokmål‎",nds: "Plattdüütsch‎",
      'nds-nl': "Nedersaksies‎",ne: "नेपाली‎",new: "नेपाल भाषा‎",ng: "Oshiwambo‎",niu: "Niuē‎",
      nl: "Nederlands‎",'nl-informal': "Nederlands (informeel)‎",nn: "norsk nynorsk‎",no: "norsk‎",
      nod: "ᨣᩴᩤᨾᩮᩥᩬᨦ‎",nov: "Novial‎",nqo: "ߒߞߏ‎",nrm: "Nouormand‎",nso: "Sesotho sa Leboa‎",
      nv: "Diné bizaad‎",ny: "Chi-Chewa‎",nys: "Nyunga‎",oc: "occitan‎",olo: "Livvinkarjala‎",
      om: "Oromoo‎",or: "ଓଡ଼ିଆ‎",os: "Ирон‎",ota: "لسان توركى‎",pa: "ਪੰਜਾਬੀ‎",pag: "Pangasinan‎",
      pam: "Kapampangan‎",pap: "Papiamentu‎",pcd: "Picard‎",pdc: "Deitsch‎",pdt: "Plautdietsch‎",
      pfl: "Pälzisch‎",pi: "पालि‎",pih: "Norfuk / Pitkern‎",pl: "polski‎",pms: "Piemontèis‎",
      pnb: "پنجابی‎",pnt: "Ποντιακά‎",prg: "Prūsiskan‎",ps: "پښتو‎",pt: "português‎",
      'pt-br': "português do Brasil‎",qu: "Runa Simi‎",qug: "Runa shimi‎",rgn: "Rumagnôl‎",
      rif: "Tarifit‎",rm: "rumantsch‎",rmf: "kaalengo tšimb‎",rmy: "romani čhib‎",rn: "Kirundi‎",
      ro: "română‎",'roa-rup': "armãneashti‎",'roa-tara': "tarandíne‎",ru: "русский‎",rue: "русиньскый‎",
      rup: "armãneashti‎",ruq: "Vlăheşte‎",'ruq-cyrl': "Влахесте‎",'ruq-latn': "Vlăheşte‎",
      rw: "Kinyarwanda‎",rwr: "मारवाड़ी‎",sa: "संस्कृतम्‎",sah: "саха тыла‎",sat: "ᱥᱟᱱᱛᱟᱲᱤ‎",
      sc: "sardu‎",scn: "sicilianu‎",sco: "Scots‎",sd: "سنڌي‎",sdc: "Sassaresu‎",
      sdh: "کوردی خوارگ‎",se: "davvisámegiella‎",sei: "Cmique Itom‎",ses: "Koyraboro Senni‎",
      sg: "Sängö‎",sgs: "žemaitėška‎",sh: "srpskohrvatski / српскохрватски‎",shi: "Tašlḥiyt/ⵜⴰⵛⵍⵃⵉⵜ‎",
      'shi-latn': "Tašlḥiyt‎",'shi-tfng': "ⵜⴰⵛⵍⵃⵉⵜ‎",shn: "ၽႃႇသႃႇတႆး ‎",'shy-latn': "tacawit‎",
      si: "සිංහල‎",simple: "Simple English‎",sjd: "Кӣллт са̄мь кӣлл‎",sje: "bidumsámegiella‎",
      sju: "ubmejesámiengiälla‎",sk: "slovenčina‎",skr: "سرائیکی‎",'skr-arab': "سرائیکی‎",
      sl: "slovenščina‎",sli: "Schläsch‎",sm: "Gagana Samoa‎",sma: "åarjelsaemien‎",
      smj: "julevsámegiella‎",smn: "anarâškielâ‎",sms: "sääʹmǩiõll‎",sn: "chiShona‎",
      so: "Soomaaliga‎",sq: "shqip‎",sr: "српски / srpski‎",'sr-ec': "српски (ћирилица)‎",
      'sr-el': "srpski (latinica)‎",srn: "Sranantongo‎",srq: "mbia cheë‎",ss: "SiSwati‎",
      st: "Sesotho‎",stq: "Seeltersk‎",sty: "себертатар‎",su: "Sunda‎",sv: "svenska‎",
      sw: "Kiswahili‎",szl: "ślůnski‎",szy: "Sakizaya‎",ta: "தமிழ்‎",tay: "Tayal‎",
      tcy: "ತುಳು‎",te: "తెలుగు‎",tet: "tetun‎",tg: "тоҷикӣ‎",'tg-cyrl': "тоҷикӣ‎",
      'tg-latn': "tojikī‎",th: "ไทย‎",ti: "ትግርኛ‎",tk: "Türkmençe‎",tl: "Tagalog‎",
      tly: "толышә зывон‎",tn: "Setswana‎",to: "lea faka-Tonga‎",tpi: "Tok Pisin‎",
      tr: "Türkçe‎",tru: "Ṫuroyo‎",trv: "Seediq‎",ts: "Xitsonga‎",tt: "татарча/tatarça‎",
      'tt-cyrl': "татарча‎",'tt-latn': "tatarça‎",tum: "chiTumbuka‎",tw: "Twi‎",ty: "reo tahiti‎",
      tyv: "тыва дыл‎",tzm: "ⵜⴰⵎⴰⵣⵉⵖⵜ‎",udm: "удмурт‎",ug: "ئۇيغۇرچە / Uyghurche‎",
      'ug-arab': "ئۇيغۇرچە‎",'ug-latn': "Uyghurche‎",uk: "українська‎",ur: "اردو‎",
      uz: "oʻzbekcha/ўзбекча‎",'uz-cyrl': "ўзбекча‎",'uz-latn': "oʻzbekcha‎",ve: "Tshivenda‎",
      vec: "vèneto‎",vep: "vepsän kel’‎",vi: "Tiếng Việt‎",vls: "West-Vlams‎",vmf: "Mainfränkisch‎",
      vo: "Volapük‎",vot: "Vaďďa‎",vro: "Võro‎",wa: "walon‎",war: "Winaray‎",wo: "Wolof‎",
      wuu: "吴语‎",xal: "хальмг‎",xh: "isiXhosa‎",xmf: "მარგალური‎",xsy: "saisiyat‎",
      yi: "ייִדיש‎",yo: "Yorùbá‎",yue: "粵語‎",za: "Vahcuengh‎",zea: "Zeêuws‎",
      zgh: "ⵜⴰⵎⴰⵣⵉⵖⵜ ⵜⴰⵏⴰⵡⴰⵢⵜ‎",zh: "中文‎",'zh-classical': "文言‎",'zh-cn': "中文（中国大陆）‎",
      'zh-hans': "中文（简体）‎",'zh-hant': "中文（繁體）‎",'zh-hk': "中文（香港）‎",'zh-min-nan': "Bân-lâm-gú‎",
      'zh-mo': "中文（澳門）‎",'zh-my': "中文（马来西亚）‎",'zh-sg': "中文（新加坡）‎",'zh-tw': "中文（台灣）‎",
      'zh-yue': "粵語‎",zu: "isiZulu"
    };
    try{
      prefLanguage = Object.keys(wdProperties['preferredLanguage'])[0];
    }catch(err){
      prefLanguage = false;  
    }
    var fallbackLanguage = !!wdProperties['fallbackLanguage'] ? wdProperties['fallbackLanguage'] : false; 
    var appFallbackLanguage = 'en'; 
    var setSelected = prefLanguage ? prefLanguage : fallbackLanguage;
    var ddopttarget = document.getElementById('wdlookuplanguage'); 
    setSelected = setSelected ? setSelected : appFallbackLanguage; 
    for(const key in languages){
      let displayLanguage = languages[key]; 
      let ddopt = document.createElement('option'); 
      ddopt.setAttribute('value', key); 
      ddopt.appendChild(document.createTextNode(displayLanguage)); 
      if(key === setSelected){
        ddopt.selected = true; 
      }
      ddopttarget.appendChild(ddopt); 
    }

  </script>
</div>
</body>
</html>
