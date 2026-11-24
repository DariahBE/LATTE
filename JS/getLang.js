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

function detectLanguage(options){
  return new Promise(function(resolve, reject){
    var language = options ? options['ISO_code'] : null;
    var nodeid = options ? options['nodeid'] : null;
    if (!language){
      const params = new URLSearchParams({ node: nodeid });
      fetch("/AJAX/getLang_async.php?" + params.toString())
        .then(response => response.json())
        .then(data => resolve(data))
        .catch(err => reject(err));
    } else {
      // If language is already known on options, resolve with normalized language object.
      const iso = normalizeLanguageCode(language);
      resolve({
        languageCode: iso,
        language: language,
        certainty: 1.0
      });
    }
  });
}

function displayLanguage(langdetect){
  const elLang = document.getElementById('detectedLanguage');
  const elCode = document.getElementById('detectedLanguageCode');
  const elCert = document.getElementById('detectedLanguageCertainty');

  if (!langdetect){
    if (typeof languageOptions !== 'undefined' && languageOptions && languageOptions.ISO_code) {
      const existingIso = normalizeLanguageCode(languageOptions.ISO_code);
      if (elCode && (!elCode.textContent || elCode.textContent === 'N/A')) {
        elCode.textContent = existingIso || 'N/A';
      }
    } else {
      if (elLang) elLang.textContent = "N/A";
      if (elCode) elCode.textContent = "N/A";
      if (elCert) elCert.textContent = "0";
      if (typeof languageOptions !== 'undefined') {
        languageOptions.ISO_code = false;
      }
    }
    return;
  }

  const rawCode = langdetect['languageCode'] || langdetect['ISO_code'] || langdetect['code'] || langdetect['lang'] || langdetect['language'];
  const iso = normalizeLanguageCode(rawCode);
  const langName = langdetect['language'] || (iso ? iso.toUpperCase() : "N/A");

  if (elLang) elLang.textContent = langName;
  if (elCode) elCode.textContent = iso || rawCode || "N/A";
  if (elCert) elCert.textContent = langdetect['certainty'] || "0";
  if (typeof languageOptions !== 'undefined' && iso) {
    languageOptions.ISO_code = iso;
  }

  const prioritize = (typeof wdProperties !== 'undefined' && wdProperties && wdProperties['prioritizeDetectedLanguage'] !== undefined)
    ? Boolean(wdProperties['prioritizeDetectedLanguage'])
    : true;

  if (prioritize && iso) {
    const dd = document.getElementById('wdlookuplanguage');
    if (dd) {
      if (!dd.querySelector(`option[value="${iso}"]`)) {
        const opt = document.createElement('option');
        opt.value = iso;
        opt.textContent = langName;
        dd.appendChild(opt);
      }
      dd.value = iso;
      dd.dispatchEvent(new Event('change'));
    }
    if (typeof updateWDPromptLangIndicator === 'function') {
      updateWDPromptLangIndicator();
    }
  }
}
