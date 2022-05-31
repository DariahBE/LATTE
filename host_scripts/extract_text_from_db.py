import argparse
from neo4j import GraphDatabase
##function imported into main-files to extract the text:
#takes commandline arguments to log in in the NEO4J database.
#and returns the text based on a given NODE ID!
def connect_and_extract(returnLang):
    parser=argparse.ArgumentParser()
    parser.add_argument('--uri', help = 'Endpoint URI of the NEO4J database.')
    parser.add_argument('--username', help = 'NEO4J username.')
    parser.add_argument('--password', help = 'NEO4J password.')
    parser.add_argument('--database', help = 'NEO4J database.')
    parser.add_argument('--nodeid', help = 'NEO4J nodeID containing the text.')
    parser.add_argument('--extractor', help = 'Which language detection engine should be used.')
    if returnLang:
        parser.add_argument('--lang', help = 'NEO4J nodeID containing the text.')

    #set empty defaults
    text = ''
    lang = ''
    engine = 'spacy'

    args = parser.parse_args()
    data = vars(args)
    uri  = data['uri']          #
    db = data['database']       #
    usr = data['username']      #
    psw = data['password']      #
    node = data['nodeid']       #match on neo4J id(node)
    engine = data['extractor']
    if returnLang:
        lang = data['lang']
    #creating connection object to the NEO4J database
    driver = GraphDatabase.driver(uri, max_connection_lifetime=3600)
    session = driver.session()

    #get the text from the database based on the node id.
    queryTextById = "MATCH (n:Text) WHERE id(n) = $nodeID RETURN n.text AS text LIMIT 1"
    textdata = session.run(queryTextById, nodeID=int(node))

    #iterate over the NEO4J results: (contains only one record - only way of accessing over the api though)
    for record in textdata:
        text = record['text']
    return [text, lang, engine]
