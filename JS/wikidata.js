
/**
 *  A simple class to collect wikidata information based on a provided wikidata QID: !
 *      
 *      INITIATE: 
 *        a = new wikibaseEntry('Q661619', wdProperties, windowmode(static or slideover) );
 *          //first argument is a valid Q-identifier: 
 *          //second argument is a dict of usersettings. 
 *      GETTING WIKIDATA DATA: 
 *        a.getWikidata(); 
 */

if (typeof createDivider !== 'function') {
  window.createDivider = function(string) {
    const divider = document.createElement('h3');
    divider.appendChild(document.createTextNode(string));
    divider.className = 'text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 pb-1.5 mt-4 mb-3 flex items-center gap-1.5';
    return divider;
  };
}

if (typeof window.normalizeLanguageCode !== 'function') {
  window.normalizeLanguageCode = function(raw) {
    if (!raw) return null;
    const str = String(raw).trim().toLowerCase();
    const nameToCode = {
      german: 'de', deutsch: 'de', de: 'de',
      french: 'fr', francais: 'fr', français: 'fr', fr: 'fr',
      english: 'en', en: 'en',
      dutch: 'nl', nederlands: 'nl', nl: 'nl',
      spanish: 'es', espanol: 'es', español: 'es', es: 'es',
      italian: 'it', italiano: 'it', it: 'it',
      latin: 'la', latina: 'la', la: 'la',
      greek: 'el', 'ancient greek': 'grc', grc: 'grc', el: 'el',
      portuguese: 'pt', portugues: 'pt', português: 'pt', pt: 'pt',
      polish: 'pl', polski: 'pl', pl: 'pl',
      russian: 'ru', ru: 'ru',
      japanese: 'ja', ja: 'ja',
      chinese: 'zh', zh: 'zh',
      arabic: 'ar', ar: 'ar',
      swedish: 'sv', sv: 'sv',
      ukrainian: 'uk', uk: 'uk',
      hungarian: 'hu', hu: 'hu',
      czech: 'cs', cs: 'cs',
      danish: 'da', da: 'da',
      finnish: 'fi', fi: 'fi',
      norwegian: 'no', no: 'no',
      turkish: 'tr', tr: 'tr',
      hebrew: 'he', he: 'he'
    };
    const clean = str.replace(/wiki$/, '').split(/[-_\s/]/)[0];
    if (nameToCode[str]) return nameToCode[str];
    if (nameToCode[clean]) return nameToCode[clean];
    return clean;
  };
}

function helper_setWDLanguages(ddopttarget){
  //TODO: not implemented yet - weird code //BUG
  // Argument in function is not properly implemented
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
  if (!ddopttarget) {
    ddopttarget = document.getElementById('wdlookuplanguage');
  }
  if (!ddopttarget) return;

  var prefLanguage = false;
  try{
    prefLanguage = Object.keys(wdProperties['preferredLanguage'])[0];
  }catch(err){
    prefLanguage = false;  
  }
  const configuredSites = (typeof wdProperties !== 'undefined' && wdProperties && wdProperties['stringmatchWikipediaTitles'])
    ? Object.keys(wdProperties['stringmatchWikipediaTitles'])
    : [];
  let firstFallbackIso = configuredSites.length > 0 ? configuredSites[0].replace(/wiki$/, '') : 'en';

  var fallbackLanguage = !!wdProperties['fallbackLanguage'] ? wdProperties['fallbackLanguage'] : false; 
  var appFallbackLanguage = 'en'; 
  var setSelected = prefLanguage ? prefLanguage : (firstFallbackIso ? firstFallbackIso : fallbackLanguage);
  setSelected = setSelected ? setSelected : appFallbackLanguage; 

  const prioritize = (typeof wdProperties !== 'undefined' && wdProperties && wdProperties['prioritizeDetectedLanguage'] !== undefined)
    ? Boolean(wdProperties['prioritizeDetectedLanguage'])
    : true;
  let detectedIso = null;
  if (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.ISO_code) {
    detectedIso = normalizeLanguageCode(languageOptions.ISO_code);
  }
  if (!detectedIso) {
    const domCodeElem = document.getElementById('detectedLanguageCode');
    const domCode = domCodeElem ? domCodeElem.textContent.trim().toLowerCase() : '';
    if (domCode && domCode !== 'n/a' && domCode !== 'none' && domCode !== 'unknown') {
      detectedIso = normalizeLanguageCode(domCode);
    }
  }
  if (prioritize && detectedIso && languages[detectedIso]) {
    setSelected = detectedIso;
  }

  ddopttarget.innerHTML = '';
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

  if (setSelected) {
    ddopttarget.value = setSelected;
  }

  if (ddopttarget && !ddopttarget.dataset.langChangeListenerBound) {
    ddopttarget.dataset.langChangeListenerBound = 'true';
    ddopttarget.addEventListener('change', function() {
      if (typeof updateWDPromptLangIndicator === 'function') {
        updateWDPromptLangIndicator();
      }
    });
  }
}

// Resolve the active string matching language, label, and auto-detection status.
function getActiveStringMatchingLanguage() {
  const prioritize = (typeof wdProperties !== 'undefined' && wdProperties && wdProperties['prioritizeDetectedLanguage'] !== undefined)
    ? Boolean(wdProperties['prioritizeDetectedLanguage'])
    : true;

  // Resolve detected document language from options or DOM.
  let detectedIso = null;
  if (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.ISO_code) {
    detectedIso = normalizeLanguageCode(languageOptions.ISO_code);
  }
  if (!detectedIso) {
    const domCodeElem = document.getElementById('detectedLanguageCode');
    const domCode = domCodeElem ? domCodeElem.textContent.trim().toLowerCase() : '';
    if (domCode && domCode !== 'n/a' && domCode !== 'none' && domCode !== 'unknown') {
      detectedIso = normalizeLanguageCode(domCode);
    }
  }

  // Resolve user configured fallback languages from preferences dictionary.
  const titlesDict = (typeof wdProperties !== 'undefined' && wdProperties && wdProperties['stringmatchWikipediaTitles'])
    ? wdProperties['stringmatchWikipediaTitles']
    : {};
  const fallbackKeys = Object.keys(titlesDict);
  const firstFallbackKey = fallbackKeys.length > 0 ? fallbackKeys[0] : 'enwiki';
  const firstFallbackIso = firstFallbackKey.replace(/wiki$/, '');

  // Check current selection in the lookup language selector.
  const dd = document.getElementById('wdlookuplanguage');
  let activeIso = (dd && dd.value && dd.value.trim() !== '') ? dd.value.trim() : null;
  if (!activeIso) {
    activeIso = (prioritize && detectedIso) ? detectedIso : firstFallbackIso;
  }
  if (!activeIso) {
    activeIso = 'en';
  }

  // Auto-detection is actively used if prioritization is enabled and active ISO matches detected ISO.
  const isAutodetected = Boolean(prioritize && detectedIso && (activeIso === detectedIso));

  // Friendly name lookup matching the strings configured in userpage.
  const activeWikiKey = activeIso + 'wiki';
  let label = null;
  if (titlesDict[activeWikiKey] && titlesDict[activeWikiKey][1]) {
    label = titlesDict[activeWikiKey][1];
  } else if (titlesDict[activeIso] && titlesDict[activeIso][1]) {
    label = titlesDict[activeIso][1];
  } else {
    const friendlyMap = {
      en: 'English', fr: 'French', de: 'German', nl: 'Dutch',
      es: 'Spanish', it: 'Italian', la: 'Latin', pt: 'Portuguese',
      pl: 'Polish', ru: 'Russian', ja: 'Japanese', zh: 'Chinese',
      ar: 'Arabic', sv: 'Swedish', uk: 'Ukrainian', el: 'Greek',
      hu: 'Hungarian', cs: 'Czech', da: 'Danish', fi: 'Finnish',
      no: 'Norwegian', tr: 'Turkish', he: 'Hebrew'
    };
    label = friendlyMap[activeIso] || (activeIso.toUpperCase() + ' (' + activeWikiKey + ')');
  }

  return {
    iso: activeIso,
    wikiKey: activeWikiKey,
    label: label,
    isAutodetected: isAutodetected,
    detectedIso: detectedIso,
    firstFallbackIso: firstFallbackIso,
    prioritize: prioritize
  };
}
window.getActiveStringMatchingLanguage = getActiveStringMatchingLanguage;

// Render language indicator in the Wikidata prompt box.
function renderWDPromptLangIndicator(container) {
  if (!container) return;
  const info = getActiveStringMatchingLanguage();
  container.innerHTML = '';

  const wrapper = document.createElement('div');
  wrapper.className = 'flex items-center justify-between gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200/80 rounded-xl text-xs text-slate-500 my-1 shadow-2xs';

  // Left side: icon and language name.
  const left = document.createElement('div');
  left.className = 'flex items-center gap-1.5 min-w-0';
  left.innerHTML = `
    <i class="fas fa-language text-slate-400 text-sm shrink-0"></i>
    <span class="text-slate-500 font-medium">Matching language:</span>
    <span class="font-bold text-slate-800 truncate" title="${info.label} (${info.wikiKey})">${info.label}</span>
  `;
  wrapper.appendChild(left);

  // Right side: clever auto-detection status badge and one-click switcher.
  const right = document.createElement('div');
  right.className = 'flex items-center gap-1.5 shrink-0';

  if (info.isAutodetected) {
    right.innerHTML = `
      <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/90 shadow-2xs cursor-pointer select-none transition hover:bg-emerald-100" title="Language automatically detected by LATTE connector. Click to toggle to fallback language.">
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
        </span>
        <i class="fas fa-wand-magic-sparkles text-[9px] text-emerald-600"></i>
        <span>Auto-detected</span>
      </span>
    `;
    const badge = right.querySelector('span');
    badge.addEventListener('click', function(e) {
      e.stopPropagation();
      const dd = document.getElementById('wdlookuplanguage');
      if (dd && info.firstFallbackIso) {
        dd.value = info.firstFallbackIso;
        dd.dispatchEvent(new Event('change'));
      }
      const inp = document.getElementById('wikidataInputPrompter');
      if (inp && typeof wdprompt === 'function') {
        wdprompt(inp.value, 0);
      }
      renderWDPromptLangIndicator(container);
    });
  } else {
    const hasDetected = Boolean(info.detectedIso);
    right.innerHTML = `
      <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200/90 shadow-2xs select-none transition ${hasDetected ? 'cursor-pointer hover:bg-slate-200' : ''}" title="${hasDetected ? 'Using configured fallback language. Click to toggle to auto-detected language (' + info.detectedIso + ').' : 'Using configured fallback language (no auto-detected language available).'}">
        <span class="inline-flex rounded-full h-2 w-2 bg-slate-400"></span>
        <i class="fas fa-arrow-rotate-left text-[9px] text-slate-500"></i>
        <span>Fallback</span>
      </span>
    `;
    if (hasDetected) {
      const badge = right.querySelector('span');
      badge.addEventListener('click', function(e) {
        e.stopPropagation();
        const dd = document.getElementById('wdlookuplanguage');
        if (dd && info.detectedIso) {
          dd.value = info.detectedIso;
          dd.dispatchEvent(new Event('change'));
        }
        const inp = document.getElementById('wikidataInputPrompter');
        if (inp && typeof wdprompt === 'function') {
          wdprompt(inp.value, 0);
        }
        renderWDPromptLangIndicator(container);
      });
    }
  }

  wrapper.appendChild(right);
  container.appendChild(wrapper);
}
window.renderWDPromptLangIndicator = renderWDPromptLangIndicator;

function updateWDPromptLangIndicator() {
  const container = document.getElementById('wdPromptLangContainer');
  if (container) {
    renderWDPromptLangIndicator(container);
  }
}
window.updateWDPromptLangIndicator = updateWDPromptLangIndicator;


class wikibaseEntry {
  Qid;                    //What is the provided Qid (used for items) || or the string to lookup when searchMode === "str"
  unprocessed             //data returned by wikidata, not parsed by toolkit
  rawData;                //data returned by the wikidata server and parsed by toollkit!
  usersettings;           //returned by the server: connected to user account AND/OR cookies AND/OR defaultsettings. 
  valid;                  //is the ID valid:
  searchMode;             //Is the query looking for a QID or a string based match. 
  // classifier;             //Loops over all Q-ids and classifies them: Disambiguation Page; Place; Person; other. 
  // Pindicators;            //Array of used P-tags in the claims-section of an entity. . 
  OutputFormattedDataBlocks;    //Object with all qid keys that are valid and for each qid all groups are added. Output generated by display...* functions are stored in each block! Display is later shown in DOM by final renderBlocks function
  windowmode              //static or slideover

  entityType;             //entity type identifier (e.g. Person, Place)

  // Validate the input dependent on the key ==> QID 
  constructor(inputvariable, settings, displaymode, by='qid', entityType = null) {
    // ID should be validated first: does it start with Q followed by int. 
    this.OutputFormattedDataBlocks = {};
    this.usersettings = settings; 
    this.windowmode = displaymode; 
    this.entityType = entityType;
    // this.Pindicators = {
    //   'PERSON': ["P1006","P1038","P1047","P1185","P119","P1196","P1213","P1233","P1290","P1317","P1415","P1422","P157","P1648","P1710","P19","P1907","P1908","P1935","P1938","P1971","P20","P2180","P22","P2421","P2460","P2498","P25","P26","P2732","P2744","P2745","P2753","P2944","P2972","P2973","P2977","P3029","P3150","P3217","P3373","P3448","P3576","P3595","P3909","P40","P4177","P4180","P4193","P4359","P4459","P451","P4602","P4789","P496","P4963","P509","P5563","P569","P570","P5726","P5756","P5756","P5956","P6038","P6059","P6167","P6234","P651","P6829","P6941","P6996","P7023","P7041","P7042","P723","P7902","P7928","P7939","P7941","P8081","P8122","P8130","P8341","P8810","P9058","P947"],
    //   'PLACE': ["P30","P36","P47","P131","P189","P206","P276","P291","P403","P610","P613","P625","P669","P706","P931","P937","P1302","P1332","P1333","P1334","P1335","P1376","P1589","P2672","P2825","P3018","P3032","P3096","P3137","P3179","P3403","P3470","P3842","P4091","P4388","P4552","P4565","P4647","P4688","P5248","P5607","P5998","P6375","P17","P150","P190","P242","P281","P296","P297","P298","P299","P300","P374","P382","P402","P429","P439","P440","P442","P454","P455","P500","P501","P507","P525","P539","P590","P605","P630","P635","P677","P716","P721","P722","P757","P761","P764","P771","P772","P773","P774","P775","P776","P777","P778","P779","P782","P804","P806","P809","P814","P821","P843","P882","P901","P909","P939","P948","P954","P964","P981","P984","P988","P1010","P1067","P1077","P1115","P1116","P1140","P1168","P1172","P1188","P1203","P1217","P1276","P1281","P1282","P1305","P1311","P1336","P1370","P1380","P1381","P1383","P1388","P1397","P1398","P1400","P1404","P1456","P1459","P1460","P1481","P1566","P1584","P1585","P1602","P1621","P1653","P1667","P1684","P1699","P1717","P1732","P1792","P1837","P1841","P1848","P1850","P1854","P1866","P1871","P1879","P1886","P1887","P1894","P1920","P1936","P1937","P1943","P1944","P1945","P1958","P1976","P2025","P2081","P2082","P2099","P2100","P2123","P2186","P2258","P2270","P2290","P2326","P2467","P2468","P2473","P2477","P2487","P2491","P2496","P2497","P2503","P2504","P2505","P2506","P2516","P2520","P2525","P2526","P2561","P2564","P2584","P2585","P2586","P2588","P2595","P2618","P2621","P2633","P2659","P2672","P2673","P2674","P2762","P2763","P2783","P2787","P2788","P2815","P2817","P2856","P2863","P2866","P2867","P2887","P2917","P2927","P2929","P2956","P2971","P2980","P2981","P2982","P2983","P3009","P3012","P3024","P3059","P3067","P3104","P3108","P3109","P3118","P3119","P3120","P3182","P3197","P3198","P3202","P3209","P3211","P3223","P3227","P3230","P3238","P3256","P3257","P3296","P3304","P3309","P3326","P3335","P3353","P3371","P3394","P3396","P3401","P3407","P3412","P3419","P3422","P3423","P3425","P3426","P3472","P3481","P3498","P3503","P3507","P3513","P3514","P3515","P3516","P3517","P3555","P3562","P3563","P3572","P3580","P3601","P3609","P3613","P3615","P3616","P3626","P3627","P3628","P3633","P3635","P3639","P3676","P3707","P3714","P3723","P3727","P3728","P3731","P3735","P3749","P3758","P3759","P3770","P3806","P3809","P3810","P3813","P3824","P3850","P3856","P3863","P3866","P3896","P3907","P3920","P3922","P3972","P3974","P3988","P3990","P3992","P3993","P4001","P4005","P4007","P4009","P4014","P4029","P4038","P4046","P4055","P4059","P4075","P4083","P4088","P4091","P4093","P4094","P4098","P4117","P4118","P4119","P4133","P4136","P4141","P4142","P4143","P4146","P4154","P4170","P4171","P4172","P4182","P4219","P4227","P4244","P4245","P4246","P4249","P4266","P4291","P4328","P4334","P4335","P4340","P4346","P4352","P4356","P4358","P4388","P4401","P4423","P4528","P4533","P4535","P4591","P4595","P4641","P4658","P4672","P4689","P4694","P4697","P4702","P4708","P4711","P4755","P4762","P4777","P4792","P4800","P4803","P4812","P4820","P4856","P4881","P5010","P5011","P5020","P5050","P5140","P5141","P5180","P5207","P5208","P5215","P5288","P5289","P5294","P5388","P5400","P5464","P5515","P5535","P5598","P5599","P5601","P5611","P5633","P5634","P5652","P5696","P5746","P5757","P5758","P5759","P5761","P5763","P5764","P5782","P5818","P5904","P5946","P5965","P6006","P6017","P6082","P6120","P6144","P6148","P6155","P6192","P6230","P6233","P6244","P6265","P6340","P200","P201","P205","P403","P469","P761","P884","P885","P974","P1200","P1404","P1717","P2516","P2584","P2856","P3006","P3119","P3326","P3394","P3707","P3866","P3871","P4002","P4190","P4202","P4279","P4511","P4528","P4568","P4614","P4661","P4792","P5079","P6148"], 
    //   'EVENT': ["P585","P710","P1343","P625","P582","P580","P1299","P1120","P1339","P1446","P1478","P1561","P1590","P2630","P3081","P3082","P11157"]
    // };
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
    let url = '';
    if (this.searchMode === 'qid'){
      url = wdk.getEntities({
        ids: [ this.Qid ],
        format: 'json', // defaults to json
        redirections: false // defaults to true
      }); 
    }else if(this.searchMode === 'str'){
      const prioritizeDetected = (typeof wdProperties !== 'undefined' && wdProperties && wdProperties['prioritizeDetectedLanguage'] !== undefined)
        ? Boolean(wdProperties['prioritizeDetectedLanguage'])
        : true;

      // Check if LATTE connector has detected a language for the current document
      let detectedLang = null;
      if (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.ISO_code) {
        detectedLang = normalizeLanguageCode(languageOptions.ISO_code);
      }
      if (!detectedLang) {
        const domCodeElem = document.getElementById('detectedLanguageCode');
        const domCode = domCodeElem ? domCodeElem.textContent.trim().toLowerCase() : '';
        if (domCode && domCode !== 'n/a' && domCode !== 'none' && domCode !== 'unknown') {
          detectedLang = normalizeLanguageCode(domCode);
        }
      }

      // Build fallback language sites from user configuration
      const configuredSites = (typeof wdProperties !== 'undefined' && wdProperties && wdProperties['stringmatchWikipediaTitles'])
        ? Object.keys(wdProperties['stringmatchWikipediaTitles'])
        : [];

      let sites = [];
      if (prioritizeDetected && detectedLang) {
        const detectedWiki = detectedLang + 'wiki';
        sites.push(detectedWiki);
        configuredSites.forEach(s => {
          if (s !== detectedWiki && !sites.includes(s)) {
            sites.push(s);
          }
        });
      } else {
        sites = [...configuredSites];
      }

      // If no fallback languages are configured, default to English Wikipedia
      if (sites.length === 0) {
        sites.push('enwiki');
      }

      var wikiLangLinksForSites = sites.join('|'); 
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
      if(sites.length > 1){
        url = url.replace('&normalize=true',''); 
      }
  }
    this.unprocessed = null; 
    return await fetch(url)
      .then(response => response.json())
      // Turns the response in an array of simplified entities
      .then(response => {if(response['error']){this.valid=false; return null;}else{return response;}})
      .then(response => this.rawData = response);
  }


 /* classify(){
      //classification not required. 
    //if you keep this function
    // convert this.rawData to this.parsedData 
    // !!!!
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
  }*/
  renderEntities(qid){
    // With all the entities categorized by the classify() call:
    // show them in the DOM. Use user preferences and highest probability to display entities.
    var madeAtLeastOneMatch = false;
    var baseBlock = {'wikilink': [], 'uri':[], 'geo':[], 'img':[], 'str':[]}; 
    var promisses = []; 
    if(this.searchMode === 'qid'){
      this.OutputFormattedDataBlocks[qid]= baseBlock; 
      this.originalData = JSON.parse(JSON.stringify(this.rawData));     // Deepcopy to preserve original raw data
      try{
        this.parsedData = wdk.parse.wd.entities(this.rawData); 
      } catch {
        // Skip linking to Wikidata if entities are unparsable
        console.error('WDK could not parse entity.');
        deleteIfExistsById("wdsearchpromptbox"); 
        deleteIfExistsById("embeddedWDConfirmationGroup"); 
        deleteIfExistsById("wdsearchpromptbox"); 
        if (typeof acceptQID === 'function') {
          acceptQID(-1);
        }
        return;
      }

      // Determine active properties for the given entity type:
      let activeProperties = {};
      if (this.entityType && this.usersettings && this.usersettings['shownPropertiesByEntity'] && this.usersettings['shownPropertiesByEntity'][this.entityType]) {
        activeProperties = this.usersettings['shownPropertiesByEntity'][this.entityType];
      } else if (this.usersettings && this.usersettings['shownPropertiesByEntity'] && this.usersettings['shownPropertiesByEntity']['_global']) {
        activeProperties = this.usersettings['shownPropertiesByEntity']['_global'];
      } else if (this.usersettings && this.usersettings['shownProperties']) {
        activeProperties = this.usersettings['shownProperties'];
      }

      let availableClaims = (this.parsedData[qid] && this.parsedData[qid].claims) ? this.parsedData[qid].claims : {};

      // Process all active configured properties for this entity type:
      for (const [propCode, propDef] of Object.entries(activeProperties)) {
        let wdPropLabel = propDef[0] || propCode;
        let isEnabled = Boolean(propDef[1]);
        let wdProcessAs = propDef[2] || 'str';
        let showMissing = Boolean(propDef[3]);
        let temporalMode = propDef[4] || 'latest';

        if (!isEnabled) {
          continue;
        }

        if (propCode in availableClaims) {
          madeAtLeastOneMatch = true;
          var wikidata_response = availableClaims[propCode];
          if(wdProcessAs === 'uri'){
            promisses.push(this.displayURI(wdPropLabel, wikidata_response, qid, propCode));
          }else if(wdProcessAs === 'geo'){
            promisses.push(this.displayCoordinateData(wikidata_response, qid, propCode));
          }else if(wdProcessAs === 'img'){
            promisses.push(this.displayImageData(wdPropLabel, wikidata_response, qid, propCode));
          }else if(wdProcessAs === 'str'){
            promisses.push(this.displayStringData(wdPropLabel, wikidata_response, qid, propCode, temporalMode));
          }
        } else if (showMissing) {
          madeAtLeastOneMatch = true;
          this.displayMissingNotice(wdPropLabel, qid, propCode, wdProcessAs);
        }
      }

      // Handle the Wikipedia links: 
      if (this.parsedData[qid] && this.parsedData[qid].sitelinks) {
        Object.keys(this.parsedData[qid].sitelinks).forEach(key => {
          let sitelinkValue = this.parsedData[qid].sitelinks[key]; 
          // If key is also in showWikipediaLinksTo:
          if(this.usersettings && this.usersettings['showWikipediaLinksTo'] && key in this.usersettings['showWikipediaLinksTo']){
            let wikipediaURI = this.usersettings['showWikipediaLinksTo'][key][0] + sitelinkValue; 
            let wikipediaAnchorText = this.usersettings['showWikipediaLinksTo'][key][1]; 
            let wikipediaClickLink = document.createElement('a'); 
            wikipediaClickLink.setAttribute('target', '_blank'); 
            wikipediaClickLink.setAttribute('href', wikipediaURI); 
            wikipediaClickLink.appendChild(document.createTextNode(wikipediaAnchorText)); 
            wikipediaClickLink.classList.add('externalURILogo', 'flex'); 
            this.OutputFormattedDataBlocks[qid]['wikilink'].push(wikipediaClickLink);
          }
        });
      }
    }

    Promise.all(promisses).then((values)=> {
      var keyToTitle = {'wikilink': 'Wikis', 'uri': 'External Identifiers', 'geo': 'Maps', 'img': 'Images', 'str': 'Literals'};
      // Output the OutputFormattedDataBlocks to DOM:
      let target = null;
      if(this.windowmode === 'slideover'){
        target = document.getElementById('slideoverDynamicContent'); 
      }else if(this.windowmode === 'static'){
        target = document.getElementById('insertWDHere'); 
      }else if(this.windowmode === 'preview'){
        target = document.getElementById('previewWDTarget');
      }

      if(!target){
        console.warn('Target container not found for Wikidata display mode: ' + this.windowmode);
        return;
      }

      let existing = target.querySelector('#WDResponseTarget') || (this.windowmode !== 'preview' ? document.getElementById('WDResponseTarget') : null);
      if(existing !== null){
        existing.remove();
      }

      let dataDivMain = document.createElement('div'); 
      dataDivMain.className = 'border-t border-slate-200/80 mt-4 pt-3';
      dataDivMain.setAttribute('id', 'WDResponseTarget');
      let wikidataTitleBlock = document.createElement('h2'); 
      wikidataTitleBlock.innerHTML = '<i class="fab fa-wikipedia-w text-indigo-500 mr-1.5"></i> Wikidata'; 
      wikidataTitleBlock.className = 'text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center'; 
      dataDivMain.appendChild(wikidataTitleBlock);

      if (this.searchMode === 'qid'){
        for (const [key, value] of Object.entries(this.OutputFormattedDataBlocks[qid])) {
          if(value.length === 0){continue;}
          let dataDivCategory = document.createElement('div'); 
          dataDivCategory.classList.add('my-2', 'py-2'); 
          let categoryTitle = (typeof createDivider === 'function')
            ? createDivider(keyToTitle[key])
            : (function(label) {
                let h = document.createElement('h3');
                h.className = 'text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 pb-1.5 mt-4 mb-3';
                h.textContent = label;
                return h;
              })(keyToTitle[key]);
          dataDivCategory.appendChild(categoryTitle); 
          for(var n = 0; n < value.length; n++){
            dataDivCategory.appendChild(value[n]); 
          }
          dataDivMain.appendChild(dataDivCategory); 
        }
      }
      target.appendChild(dataDivMain); 

      if(typeof buildCaroussel === 'function'){
        try { buildCaroussel(); } catch(err) { console.warn('Caroussel error:', err); }
      }
      if(typeof buildMaps === 'function'){
        try { buildMaps(); } catch(err) { console.warn('Maps error:', err); }
      }

      if(!(madeAtLeastOneMatch)){
        let emptyNotice = document.createElement('p');
        emptyNotice.className = 'text-xs text-slate-400 italic py-2';
        emptyNotice.textContent = 'Wikidata returned no property tags within project scope.';
        dataDivMain.appendChild(emptyNotice);
      }
    });

  }

  displayMissingNotice(label, q, property, processAs){
    var cat = (processAs === 'uri') ? 'uri' : (processAs === 'geo' ? 'geo' : (processAs === 'img' ? 'img' : 'str'));
    var into = this.OutputFormattedDataBlocks[q][cat];
    var pelement = document.createElement('p');
    pelement.className = 'my-1';
    pelement.innerHTML = "<a href='https://www.wikidata.org/wiki/Property:" + property + "' target='_blank' class='font-bold'>" + label + "</a>: <span class='text-[11px] text-slate-400 italic'>No data available</span>";
    into.push(pelement);
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
  displayCoordinateData(wdresponse, q, property){
    var into = this.OutputFormattedDataBlocks[q]['geo'];
    //if claims contain a key 'P625' then there's coordinate Data for the returned XHR call: show it. 
    //DO NOT use wikimedia tileserver: usage policy does not support intended use. In stead use OSM:
    //https://operations.osmfoundation.org/policies/tiles/
    //response['claims']['P625'] ==> WGS84 coordinating system !!
    // https://www.wikidata.org/wiki/Property_talk:P625  
    /**can be one to many! In that case the first record is the preferred record. Show both, but with separate marker!*/
    var geoDiv = document.createElement('div'); 
    //sticky class required when viewing from URI stable endpoint!
    geoDiv.classList.add('geocontainer_for_wikidata_coords', 'm-auto', 'sticky'); 
    geoDiv.setAttribute('id', property); 
    geoDiv.setAttribute('data-coordinates', JSON.stringify(wdresponse));
    geoDiv.setAttribute('data-wdprop', property);
    geoDiv.classList.add('wdminidiv');
    var targetDivForMap = document.createElement('div'); 
    targetDivForMap.setAttribute('id', property+'_map'); 
    geoDiv.appendChild(targetDivForMap);
    into.push(geoDiv); 
  }

  displayImageData(label, image, q, property){
    //image => https://www.wikidata.org/wiki/Property_talk:P18 
    //    P18: formatter: https://commons.wikimedia.org/wiki/File:$1 
    //Coat of arms => https://www.wikidata.org/wiki/Property_talk:P154 
    //    P154: formatter: https://commons.wikimedia.org/wiki/File:$1
    //commemorative plaque => https://www.wikidata.org/wiki/Property_talk:P1801 
    //    P1801: formatter: https://commons.wikimedia.org/wiki/File:$1   
    //////LET'S assume that all mediafiles have the same formatter; as long as P-content in xhr call is universal, this will work; 
    //Only run when image.length > 0:
    if (image.length == 0){return}
    var into = this.OutputFormattedDataBlocks[q]['img'];
    //let's assume that the image variable can hold 2 or more images, we need a for loop and caroussel to display this. 
    var carousselDiv = document.createElement('div'); 
    var labelDiv = document.createElement('a');
    labelDiv.setAttribute('href', 'https://www.wikidata.org/wiki/Property_talk:'+property);
    labelDiv.setAttribute('target', '_blank');
    labelDiv.appendChild(document.createTextNode(label));
    labelDiv.classList.add('font-bold');
    carousselDiv.classList.add('caroussel_for_wikidata_images', 'wdminidiv', 'm-auto');
    carousselDiv.setAttribute('id', property);
    carousselDiv.setAttribute('data-content', JSON.stringify(image));
    carousselDiv.appendChild(labelDiv);
    into.push(carousselDiv);
    //A separate caroussel JS file handles the actual display of data. 
  }

  async displayStringData(label, value, q, property, temporalMode = 'latest'){
    var langElem = document.getElementById('wdlookuplanguage');
    var labelLanguagePreference = (langElem && langElem.value) ? langElem.value : (this.usersettings && this.usersettings['preferredLanguage'] ? Object.keys(this.usersettings['preferredLanguage'])[0] : 'en');
    var into = this.OutputFormattedDataBlocks[q]['str'];
    var pelement = document.createElement('p');
    pelement.className = 'my-1';
    let propertyset = (this.originalData && this.originalData['entities'] && this.originalData['entities'][q] && this.originalData['entities'][q]['claims']) ? this.originalData['entities'][q]['claims'][property] : null;
    if(!propertyset || propertyset.length === 0){
      return;
    }
    let propertyDataType = propertyset[0]['mainsnak']['datatype']; 

    if(propertyDataType === 'time'){
      var date = new Date(value); 
      var formattedDate = isNaN(date.getTime()) ? value : date.toLocaleDateString();
      var showAs = "<a href='https://www.wikidata.org/wiki/Property:"+property+"' target='_blank' class='font-bold'>"+label+"</a>: <span>"+formattedDate+"</span>"; 
      pelement.innerHTML = showAs; 
      into.push(pelement); 
    }else if (propertyDataType === 'wikibase-item'){
      let newElements = []; 
      let multidata = []; 
      var valueArray = Array.isArray(value) ? value : [value];
      var urlForValueLookup = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids="+valueArray.join('|')+"&props=labels&format=json&origin=*"; 
      return await fetch(urlForValueLookup)
        .then(response => response.json())
        .then(response => {
          valueArray.forEach(qvalue => {
            let labelList = (response['entities'] && response['entities'][qvalue] && response['entities'][qvalue]['labels']) ? response['entities'][qvalue]['labels'] : {};
            let showToUser = qvalue; 
            if(labelLanguagePreference in labelList){
              showToUser = labelList[labelLanguagePreference]['value'];
            }else if('en' in labelList){
              showToUser = labelList['en']['value'];
            }
            multidata.push([showToUser, qvalue]); 
          });
          let showAs = "<a href='https://www.wikidata.org/wiki/Property:"+property+"' target='_blank' class='font-bold'>"+label+"</a>: "; 
          const anchors = multidata.map(([text, href]) => {
            return "<a href='https://www.wikidata.org/wiki/"+href+"' target='_blank' class='hover:underline text-indigo-600'>"+text+"</a>"; 
          });
          showAs = showAs + "<span>"+anchors.join(', ')+"</span>"; 
          pelement.innerHTML = showAs; 
          newElements.push(pelement);
        }).then(function(){
          into.push(...newElements); 
        }).catch(err => {
          console.warn('Error resolving wikibase item labels:', err);
          pelement.innerHTML = "<a href='https://www.wikidata.org/wiki/Property:"+property+"' target='_blank' class='font-bold'>"+label+"</a>: <span>"+valueArray.join(', ')+"</span>";
          into.push(pelement);
        });

    }else if (propertyDataType === 'monolingualtext'){
      var valueArr = Array.isArray(value) ? value : [value];
      if (valueArr.length === 1){
        var showAs = "<a href='https://www.wikidata.org/wiki/Property:"+property+"' target='_blank' class='font-bold'>"+label+"</a>: <span>"+valueArr[0]+"</span>"; 
      }else{
        var showAs = "<a href='https://www.wikidata.org/wiki/Property:"+property+"' target='_blank' class='font-bold'>"+label+"</a>: <ul class='list-disc list-inside ml-2 text-xs'>";
        for(let i = 0; i < valueArr.length; i++){
          showAs += '<li>'+valueArr[i]+'</li>'; 
        }
        showAs += '</ul>';
      }
      pelement.innerHTML = showAs; 
      into.push(pelement); 

    }else if (propertyDataType === 'quantity'){
      // Extract all statements with temporal qualifiers (P585: point in time):
      let statementsWithTime = [];
      for (let i = 0; i < propertyset.length; i++) {
        let stmt = propertyset[i];
        if (stmt.mainsnak && stmt.mainsnak.datavalue && stmt.mainsnak.datavalue.value) {
          let rawAmt = stmt.mainsnak.datavalue.value.amount;
          let unit = stmt.mainsnak.datavalue.value.unit || '';
          let timeStr = null;
          let year = null;
          if (stmt.qualifiers && stmt.qualifiers.P585 && stmt.qualifiers.P585[0] && stmt.qualifiers.P585[0].datavalue && stmt.qualifiers.P585[0].datavalue.value) {
            timeStr = stmt.qualifiers.P585[0].datavalue.value.time;
            let m = timeStr.match(/^[+-]?(\d{4})/);
            if (m) {
              year = parseInt(m[1], 10);
            }
          }
          statementsWithTime.push({
            statement: stmt,
            amount: rawAmt,
            unit: unit,
            timeStr: timeStr,
            year: year,
            rank: stmt.rank
          });
        }
      }

      // Sort chronologically descending:
      statementsWithTime.sort((a, b) => {
        if (a.year !== null && b.year !== null) return b.year - a.year;
        if (a.year !== null) return -1;
        if (b.year !== null) return 1;
        return 0;
      });

      // Primary statement is preferred rank if present, or latest year:
      let primaryEntry = statementsWithTime.find(s => s.rank === 'preferred') || statementsWithTime[0];
      if (!primaryEntry) {
        return;
      }

      let propertyUnit = primaryEntry.unit;
      const formatAmount = (numStr) => {
        let num = Number(numStr);
        return isNaN(num) ? numStr : num.toLocaleString();
      };

      const renderQuantityElement = (unitText) => {
        let unitSuffix = unitText ? (' ' + unitText) : '';
        let primaryFormatted = formatAmount(primaryEntry.amount) + unitSuffix;
        let yearBadge = primaryEntry.year ? (" <span class='text-[11px] font-medium text-slate-500 bg-slate-100 border border-slate-200/80 px-1.5 py-0.5 rounded ml-1'>" + primaryEntry.year + "</span>") : '';

        // Check if evolution history should be appended:
        let historyHtml = '';
        if (temporalMode === 'history' && statementsWithTime.length > 1) {
          let validWithYears = statementsWithTime.filter(s => s.year !== null);
          let seenYears = new Set();
          let uniqueYearsList = [];
          for (let s of validWithYears) {
            if (!seenYears.has(s.year)) {
              seenYears.add(s.year);
              uniqueYearsList.push(s);
            }
          }

          if (uniqueYearsList.length > 1) {
            let listRows = uniqueYearsList.map(item => {
              return "<div class='flex items-center justify-between text-[11px] py-0.5 pr-2 hover:bg-slate-50 rounded'>" +
                "<span class='font-medium text-slate-600'>" + item.year + "</span>" +
                "<span class='text-slate-800 font-mono'>" + formatAmount(item.amount) + unitSuffix + "</span>" +
                "</div>";
            }).join('');

            historyHtml = "<details class='mt-1 text-xs'>" +
              "<summary class='cursor-pointer text-indigo-600 hover:text-indigo-700 font-medium text-[11px] select-none py-0.5'>" +
              "View recorded evolution (" + uniqueYearsList.length + " data points)</summary>" +
              "<div class='mt-1.5 ml-1 pl-2.5 border-l-2 border-indigo-200 max-h-36 overflow-y-auto space-y-0.5'>" +
              listRows +
              "</div></details>";
          }
        }

        var showAs = "<a href='https://www.wikidata.org/wiki/Property:"+property+"' target='_blank' class='font-bold'>"+label+"</a>: <span>"+primaryFormatted+"</span>" + yearBadge + historyHtml; 
        pelement.innerHTML = showAs; 
        into.push(pelement); 
      };

      // Resolve unit if it is a Wikidata entity:
      if (/^(http.*wikidata.org\/entity\/Q[0-9]*)$/.test(propertyUnit)) {
        let Qmatch = propertyUnit.match(/Q[0-9]*/)[0]; 
        let unitLookupUrl = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids="+Qmatch+"&props=labels|claims&format=json&origin=*";
        return await fetch(unitLookupUrl)
          .then(response => response.json())
          .then(response => {
            let labelList = (response['entities'] && response['entities'][Qmatch] && response['entities'][Qmatch]['labels']) ? response['entities'][Qmatch]['labels'] : {};
            let claimslist = (response['entities'] && response['entities'][Qmatch] && response['entities'][Qmatch]['claims']) ? response['entities'][Qmatch]['claims'] : {};
            let claimSymbol = '';

            if('P5061' in claimslist && claimslist['P5061'].length > 0){
              let prefstatement = claimslist['P5061'][0];
              for(let i = 0; i < claimslist['P5061'].length; i++){
                if(claimslist['P5061'][i].rank === 'preferred'){
                  prefstatement = claimslist['P5061'][i]; 
                }
              }
              if (prefstatement && prefstatement['mainsnak'] && prefstatement['mainsnak']['datavalue']) {
                claimSymbol = prefstatement['mainsnak']['datavalue']['value'].text || '';
              }
            }

            if(!claimSymbol){
              if(labelLanguagePreference in labelList){
                claimSymbol = labelList[labelLanguagePreference]['value']; 
              }else if('en' in labelList){
                claimSymbol = labelList['en']['value']; 
              }else if(Object.keys(labelList).length > 0){
                claimSymbol = labelList[Object.keys(labelList)[0]]['value']; 
              }
            }

            renderQuantityElement(claimSymbol);
          }).catch(err => {
            console.warn('Error resolving unit from Wikidata:', err);
            renderQuantityElement('');
          });
      } else {
        renderQuantityElement('');
      }

    }else{
      var rawString = Array.isArray(value) ? value.join(', ') : String(value);
      var showAs = "<a href='https://www.wikidata.org/wiki/Property:"+property+"' target='_blank' class='font-bold'>"+label+"</a>: <span>"+rawString+"</span>"; 
      pelement.innerHTML = showAs; 
      into.push(pelement); 
    }
  }

  async displayURI (parent, identifierOfEntity, q, p){
    /**
     * Be carefull, you have one to many relations where one wikidata Q-id matches multiple remote identifiers of a single project!
     */
    var into = this.OutputFormattedDataBlocks[q]['uri'];
    // get the P1630Property of the element: Needs the parent element
    if (identifierOfEntity.length > 1){
      var oneToMany = 1;
    }else{
      var oneToMany = 0;
    }
    var urlForPatternRequest = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids="+p+"&props=claims&format=json&origin=*"
    // combine that with the identifierOfEntity
    return await fetch(urlForPatternRequest)
      .then(response => response.json())
      .then(response => {
        //according to docs: https://www.wikidata.org/wiki/Property:P1630 there's only one paramter to be updated: $1
        var urlPattern = response.entities[p].claims.P1630[0].mainsnak.datavalue.value;
        //generate HTML: 
        if(oneToMany === 0){
          var URI = urlPattern.replace('$1', identifierOfEntity ); 
          var URLString = document.createElement('p'); 
          var URLLabel = document.createElement('span');
          var URLLink = document.createElement('span'); 
          var URLAnchor = document.createElement('a'); 
          var URLAnchorText = document.createTextNode(identifierOfEntity); 
          URLLabel.classList.add('font-bold'); 
          URLLabel.appendChild(document.createTextNode(parent+': '));
          URLAnchor.setAttribute('href', URI); 
          URLAnchor.setAttribute('target', '_blank'); 
          URLAnchor.classList.add('externalURILogo');
          URLAnchor.appendChild(URLAnchorText); 
          URLLink.appendChild(URLAnchor);
          //add output: 
          URLString.appendChild(URLLabel);
          URLString.appendChild(URLLink);
          into.push(URLString);
        }else{
          var URLDiv= document.createElement('div');
          var DIVHeader = document.createElement('p');
          var DIVHeaderLabel = document.createElement('span');
          var DIVHeaderText = document.createTextNode(parent+': '); 
          DIVHeaderLabel.classList.add('font-bold'); 
          DIVHeaderLabel.appendChild(DIVHeaderText);
          DIVHeader.appendChild(DIVHeaderLabel);
          URLDiv.appendChild(DIVHeader); 
          DIVHeaderLabel.classList.add('fond-bold'); 
          var LinksInUL = document.createElement('ul'); 
          for (var n = 0; n < identifierOfEntity.length; n++){
            var URI = urlPattern.replace('$1', identifierOfEntity[n] ); 
            var listItem = document.createElement('li'); 
            listItem.classList.add('wdToMany'); 
            listItem.classList.add('externalURILogo'); 
            var URLAnchor = document.createElement('a'); 
            var URLAnchorText = document.createTextNode(identifierOfEntity[n]); 
            URLAnchor.setAttribute('href', URI); 
            URLAnchor.setAttribute('target', '_blank'); 
            URLAnchor.appendChild(URLAnchorText); 
            listItem.appendChild(URLAnchor);
            LinksInUL.appendChild(listItem); 
          }
          URLDiv.appendChild(LinksInUL);
          into.push(URLDiv);
        }
      });
}

};
