import json
from utils import utils
settings = json.load(open('import_settings.json'))
print(settings)

#read the settings and store them to variables: 
#   files: 
textsfilename = settings['file_settings']['texts_file']
entitiesfilename = settings['file_settings']['entities']
annotations = settings['file_settings']['annotations']
#   texts: 
textpk = settings['texts_config']['primary_key']

#PSEUDOCODE: thought exercise how would the import work: 

#read the config file: 
#    extract texts
#    extract entities
#    extract annotations


#texts = provide it as a JSON file: 
#   - TEXID (int)
#   - TEXT (str)
#   - Properties (dict)
texlabel = settings['texts_config']['label']
texts = json.load(open(f'data/{textsfilename}'))
texdata = {}
#creating nodes should be done with BATCH: https://medium.com/neo4j/5-tips-tricks-for-fast-batched-updates-of-graph-structures-with-neo4j-and-cypher-73c7f693c8cc
batch = []
for text in texts['texts']: 
    pk = text[textpk]
    #print(utils.generate_uuid4())
    nodeproperties = {}
    # props = text['properties']
    # for prop, value in props.items(): 
    #     if prop in settings['texts_config']['property_translations']: 
    #         key = settings['texts_config']['property_translations'][prop]
    #     else: 
    #         key = prop
    #     nodeproperties[key] = value

    for props in text['properties']:
        nodeproperties = {
            settings['texts_config']['property_translations'].get(prop, prop): value
            for prop, value in text['properties'].items()
        }
    #props['uuid'] = utils.generate_uuid4()
    batch.append(nodeproperties)
texdata['batch'] = batch

print(texdata)
#BATCHQUERY FOR TEXT BECOMES: 
batchquery = f" UNWIND $batch as row \
CREATE (n:{texlabel}) \
SET n += row"
#texts config = 
#   - translate properties to keys used in NEO4J. 
#   - UUID-creation
#      OR: if there is a UUID in the properties, which one is it. Then that gets reused.

