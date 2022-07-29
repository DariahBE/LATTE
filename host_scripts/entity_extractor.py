# -*- coding: utf-8 -*-
from extract_text_from_db import connect_and_extract as extractor

import sys
import json
import spacy
from spacy import displacy
text = extractor(True)
lang = text[1]
text = text [0]
#print(text)
lookupModel = {
    'el': 'el_core_news_lg',
    'en': 'en_core_web_lg',
    'la': 'la_latin_spacy_model'
}
usedModel = lookupModel[lang]
nlp = spacy.load(usedModel)
parse = nlp(text)

labelLookup = {
    'PERSON': 'Person',
    'GPE': 'Place'
}

total = 0
result = []
for p in parse.ents:
    if p.label_ in labelLookup:
        total+=1
        subresult = {
            'text': p.text,
            'label': p.label,
            'labelTex':labelLookup[p.label_],
            'startPos': p.start_char,
            'endPos': p.end_char-1
        }
        result.append(subresult)
print(json.dumps({'meta': {'found_entities_number': total, 'used_model': usedModel}, 'data':result}))
