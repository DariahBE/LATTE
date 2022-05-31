# -*- coding: utf-8 -*-
baseDoc = "C:/Users/u0118112/OneDrive - KU Leuven/DARIAH/2021 - 2025/webDevelopment/V1/host_scripts/"
import sys
import argparse
import json
import spacy
from spacy import displacy
parser=argparse.ArgumentParser()
parser.add_argument('--text', help='provide the text to extract entities from.')
parser.add_argument('--lang', help='provide a valid ISO 639-1 language code.')
args=parser.parse_args()
data = vars(args)
x = open(baseDoc+"b.txt", 'w+', encoding='utf8')
x.write(data['text'].replace("\\n","\n"))
x.close()

text  = data['text'].replace("\\n","\n")
#print(text)
lang = data['lang']
lookupModel = {
    'el': 'el_core_news_lg',
    'en': 'en_core_web_lg'
}

usedModel = lookupModel[lang]
nlp = spacy.load(usedModel)
parse = nlp(text)

labelLookup = {
    'PERSON': 'Person',
    'GPE': 'Place'
}

total = len(parse)
result = []
for p in parse.ents:
    if p.label_ in labelLookup:
        subresult = {
            'text': p.text,
            'label': p.label,
            'labelTex':labelLookup[p.label_],
            'startPos': p.start_char,
            'endPos': p.end_char
        }
        result.append(subresult);
print(json.dumps({'meta': {'found_entities_number': total, 'used_model': usedModel}, 'data':result}))
