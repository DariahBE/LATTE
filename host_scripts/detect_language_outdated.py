# -*- coding: utf-8 -*-
baseDoc = "C:/Users/u0118112/OneDrive - KU Leuven/DARIAH/2021 - 2025/webDevelopment/V1/host_scripts/"
x = open(baseDoc+"textReceivedBy__detect_language.txt", 'w+', encoding="utf8")

import sys
import argparse
import json
import spacy
from spacy.language import Language
from spacy_langdetect import LanguageDetector

parser=argparse.ArgumentParser()

parser.add_argument('--text', help='provide the text to extract a language from.')
args=parser.parse_args()

args=parser.parse_args()
data = vars(args)
text  = data['text'].replace("\\n","\n")
x.write(text)
x.flush()
x.close()
def get_lang(nlp, name):
    return LanguageDetector()

nlp = spacy.load("en_core_web_sm")
Language.factory("language_detector", func=get_lang)
nlp.add_pipe('language_detector', last=True)
lookup= {
    'el': 'Greek',
    'grc': 'Ancient Greek'
}
doc = nlp(text)
lang = doc._.language['language']               #returns the ISO 639-1 code of a language
langString = lookup[lang]
certainty = doc._.language['score']
print(json.dumps({'languageCode':lang, 'language': langString, 'certainty':certainty}))
