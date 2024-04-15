# LATTE IMPORT
Importing data in LATTE is done with the help of the provided script and settings file. The tool expects the data to be provided as .JSON files. When writing out the data to JSON it's important to adhere to the correct datatypes. 

If you are familiar with NEO4J and the Cypher query language, you can use the standard way of importing data as described [in the NEO4J documentation](https://neo4j.com/docs/getting-started/data-import/)

## Required datafiles: 
### Texts: 
Texts are provided as a JSON file, a base skeletton for your texts look like this: 
```
{
    "texts": [
      {
        "texid": 1, 
        "text": "lorem ipsum dolor amet",
        "properties": {
          "chosenid" : "4458",
          "language" : "English",
          "publication" : "G.Pap4"
        }
      }, 
      {
        "texid": 2, 
        "text": "the quick brown fox jumps over the lazy dog",
        "properties": {
          "chosenid" : "85478",
          "language" : "English",
          "publication" : "G.Pap7"
        }
      }
    ]
  }
```
each text is stored as one JSON object inside the `texts` list. For every text there are three required fields:
    - texid: the unique id of the text
    - text: the text itself
    - properties: A dictionary with all the text properties that should be in the database. 

#### Properties:
It's completely up to the user which properties are stored in the LATTE backend, there is one property that is required though: Unique IDs in the UUID4 format. These can be automatically generated during the import, or can be provided as an additional property. 

If you want to store additional properties, you can do so by adding them to the `properties` dictionary. 

    

## Data types: 
A value encoded as `'1'` will be stored in the database as a string, a value encoded as `1` will be stored as an integer.


##