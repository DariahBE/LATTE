# -*- coding: utf-8 -*-
from extract_text_from_db import connect_and_extract as extractor
import json

#choose which language detector is being used:      #langid or spacy
data = extractor(False)
text = data[0]
implement = data[2]

if implement == 'spacy':
    import spacy
    from spacy.language import Language
    from spacy_langdetect import LanguageDetector
    def get_lang(nlp, name):
        return LanguageDetector()
    nlp = spacy.load("en_core_web_sm", disable=['tokenizer', 'ner', 'textcat'])
    Language.factory("language_detector", func=get_lang)
    nlp.add_pipe('language_detector', last=True)
    doc = nlp(text)
    lang = doc._.language['language']               #returns the ISO 639-1 code of a language
    certainty = doc._.language['score']

elif implement == 'langid':
    from langid.langid import LanguageIdentifier, model
    lang_identifier = LanguageIdentifier.from_modelstring(model, norm_probs=True)
    doc = lang_identifier.classify(text) # ('en', 0.999999999999998)
    lang = doc[0]
    certainty = doc[1]

lookup= {
    'el': 'Greek',
    'grc': 'Ancient Greek',
    'la': 'Latin', 
    'en': 'English'
}
langString = lookup[lang]
print(json.dumps({'languageCode':lang, 'language': langString, 'certainty':certainty}))
