# -*- coding: utf-8 -*-
baseDoc = "C:/Users/u0118112/OneDrive - KU Leuven/DARIAH/2021 - 2025/webDevelopment/V1/host_scripts/"

import sys
import argparse
import json
import spacy
from spacy import displacy

#parser=argparse.ArgumentParser()
#parser.add_argument('--text', help='provide the text to extract entities from.')
#parser.add_argument('--lang', help='provide a valid ISO 639-1 language code.')
#args=parser.parse_args()
#data = vars(args)
#text  = data['text']
#lang = data['lang']
lang = 'el'
text = """ἔτους ιϛ|digit=16| τοῦ καὶ ιγ|digit=13| Χοίαχ δ|digit=4| ἐν
Πα-θύρει
ἐφʼ
Ἑρμίου
τοῦ παρὰ
Πανίσκου
ἀγορανόμου.
ἐπελύσατο
Πετεαρσεμθεὺς
καὶ Πετεσοῦχος των*
Πανοβχού(νιος)
τοῦ
Τοηοῦς*
καὶ τους* τούτων ἀδε(λφοὶ)
δάνειον χαλκοῦ (ταλάντων) β|digit=2| ἃ ἐδάνεισεν
αὐτοῖς
Πετεαρσεμθεὺς Ἀλμαφέως
κατὰ συνγρα(φὴν) δα(νείου) τὴν ετεθεῖσαν* ἐπὶ
τοῦ ἐν Παθύρει ἀρχείου ἐν τῷ
ιε|digit=15| τοῦ καὶ ιβ|digit=12| (ἔτει)· ὃς καὶ παρὼν
ἐπὶ τοῦ ἀρχείου Πετεαρσεμθεὺς
Ἀλμαφέως ἀνομολογήσατο
<ἀπέχειν> παρὰ
Πετεαρσεμθέως
τοῦ
Πανοβχού(νιος) καὶ τους* τούτου ἀδε(λφῶν)
τας* τοῦ σημαινομενων* χα(λκοῦ) (τάλαντα) β|digit=2|,
καὶ μὴ ἐπελεύσασθαι*
Πετεαρ-σεμθεία
μηδʼ ἄλλον τινὰ τῶν
παρʼ αὐτοῦ ἐπὶ τὸν
Πετεαρσεμθέα
καὶ τοὺς ἀδελφοὺς μηδʼ ἐπʼ ἄλλον
τινὰ τῶν παρʼ αὐτῶν. εἰ δὲ μή,
ἥ τʼ ἔφοδος ἄκυρος ἔστω, καὶ
προσ-αποτεισάτω
ὁ ἐπελθὼν ἐπίτ(ιμον)
παρα-χρῆμα
χα(λκοῦ) (τάλαντα) ε|digit=5| καὶ ἱερὰ(ς) βα(σιλεῦσι) ἀργυρίου
ἐπισήμου (δραχμὰς) ρ|digit=100|, καὶ μηθὲν ἧσσον
κύριον εἶναι κατὰ <τὰ> προγεγρα(μμένα).
τοῦτου* δʼ ἐστὶν τὸ ὀφείλημα
ἃ* ὤφειλεν
Ἁρπὼς Παβῦτος
καὶ τη*
τούτου γυναικει*
Ταρεησιος*
.
Ἑρμίας
ὁ παρὰ
Πανίσκου
κεχρη(μάτικα).
ἐπίλυσις <πρὸς> Πετεαρσεμθέα
καὶ τοὺς ἀδε(λφοὺς)
δα(νείου) χα(λκοῦ) (ταλάντων) β|digit=2| ἃ ἐδά(νεισεν) αὐτῶι Πετεαρσεμθε(ὺς)
Ἀλμα(φέως)
.
"""

x = open(baseDoc+"b.txt", 'w+', encoding='utf8')
x.write('text')
x.close()

lookupModel = {
    'el': 'el_core_news_lg',
    'en': 'en_core_web_lg'
}
x = open(baseDoc+"c.txt", 'w+', encoding="utf8")


#print('USING MODEL: ')
#print(lookupModel[lang])
usedModel = lookupModel[lang]
nlp = spacy.load(usedModel)
parse = nlp(text)

#print('FOUND entities:')
total = len(parse)
result = []
for p in parse.ents:
    subresult = {'text': p.text, 'label': p.label, 'labelTex':p.label_}
    result.append(subresult);
    #print(p.text, p.label, p.label_)
    #print(p.label, p.label_)
    x.write(p.text+','+str(p.label)+','+p.label_)
    x.write('\n')
x.close()
print(json.dumps({'meta': {'found_entities_number': total, 'used_model': usedModel}, 'data':result}))
