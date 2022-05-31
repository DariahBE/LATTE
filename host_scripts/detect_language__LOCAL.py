# -*- coding: utf-8 -*-

import sys
#import argparse
import json
import spacy
from spacy.language import Language
from spacy_langdetect import LanguageDetector
#CONSIDER: https://polyglot.readthedocs.io/en/latest/Detection.html#supported-languages

#parser=argparse.ArgumentParser()
#parser.add_argument('--text', help='provide the text to extract a language from.')
#args=parser.parse_args()
#args=parser.parse_args()
#data = vars(args)
#text  = data['text']
text = """|gap=unknown|
clemeṇtia piet[atis] vestrae , domini per[petui] [?] |gap=unknown| |vac=unknown|
Constanti et Con[sta]ṇs victores semper [Augusti] |gap=20| [militi]bus suis praesertim ex protectoris* immo his
qui al[ac]riteṛ [ob]ṣẹquium suum exḥ[ibentes] [benefi]c̣ịạ ṿẹ[stra] [ipsi] [mer]ere videntur pṛọvide|gap=4||gap=3| ṿeṇit
ego|gap=1|emqueọ |gap=3|ẹ|gap=4||gap=1|ẹ|gap=2|ẹxc̣|gap=3|tị|gap=1|o|gap=10|g̣ẹṇṭẹ|gap=4|ẹẹ ṭṛaditus in vexillạṭione Parthusagiṭṭariorum
degentium Diospoli provincia[e] Tḥẹḅạịḍọṣ superịọṛis ve[ru]ṃ ẹ[mensos] [post] [annos] ṭṛiginta et tres|num=33| directus a Senecione antehac
@^inline^comite@⟦comiee⟧ limitis eịuṣḍem p̣ṛọṿịnciae ducere ḅḷeṃniorum* gentis refug̣ạṣ ad sacra vestia* pietatis vestrae Constantinopolim
eọ peṛṛẹx̣ịṃụṣ cum legatis ṃemoratae geṇṭịṣ cụṃq̣ụẹ c[omi]te eiusdem lim[iti]s atque obtulitis* eis clementiae vestrae
ṃẹ ẹ ducenạṛiọ diṿịṇitas vestra veneṛạṇdam purpuram suam adọṛ[ar]e ịụssit praeceptusque itaque producere memoratos
legạṭọṣ ịṇ p̣ạtriam suam cum quibus trịennẹ tempus exigi* remeandoque [ad] [sa]c̣rum comitatum vestrum tirones ex provincia
Thebaịḍọṣ ạḍ[u]xi* quos Hierapoḷị tradidi et ita daṭạ vacatione mihị [promo]ṿere me clementia praefectum alae dionysada*
pṛọṿịṇciae Aegypṭị vestra dignata est verum insinụạ[t]ịṣ ṣ[acri]ṣ ḷitter[is] [Vala]c̣ịọ comiti officium respondit allegasse
cumq[ue] [pateat] ex suffragio eos pr[omotos] fuiṣṣẹ me vero iudicio sacro ideo
al[i]osqụẹ [h]uiusceṃọdi ẹpistulas homines|gap=9| ⟦ideo⟧ |vac=unknown|
iụxṭạ [su]p̣ṛạ[dictos] ạp̣ịces vestros tribu|gap=4| p̣raefecturae alae Dionỵsiados amọṭịṣ p̣er suffragium habentibus ipsorum castrorum promotionem me constitui clementia vestra iubere dignetur
solitị* contempḷạṭịone memoraṭorum ⟦laborum⟧ ⟦meorum⟧ ⟦et⟧ ⟦quos⟧ ⟦sedes⟧ ⟦|gap=3|o⟧ ⟦vide[o]r⟧ ⟦habere⟧ ⟦providẹrẹ⟧ ⟦mịhi⟧ ⟦largissima⟧
⟦pietạs⟧ ⟦vestra⟧ ⟦dignetur⟧ ⟦unde⟧ ⟦possim⟧ ⟦cotiḍianum⟧ ⟦victum⟧ ⟦adquireṛẹ⟧ et hoc consecutus agam aeterno imp̣erio
vestro maximas gratias |vac=unknown| """

def get_lang(nlp, name):
    return LanguageDetector()

nlp = spacy.load("en_core_web_sm")
Language.factory("language_detector", func=get_lang)
nlp.add_pipe('language_detector', last=True)
lookup= {
    'el': 'Greek',
    'grc': 'Ancient Greek',
    'ca': 'Catalan'
}
doc = nlp(text)
lang = doc._.language['language']               #returns the ISO 639-1 code of a language
langString = lookup[lang]
certainty = doc._.language['score']
print(json.dumps({'languageCode':lang, 'language': langString, 'certainty':certainty}))
