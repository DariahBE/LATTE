# Custom API

The `$api_settings` variabel in config.php is a nested array/dictionary that contains API profiles. Each API profile has a unique string key, which should be passed to the API-endpoint as part of the GET request. The API profiles have a 'secret' key, which can be False for open APIs or a string value for custom APIs. The profile is read from the GET request using the `profile` key in the URL. 

## Implementation constraints:
The API endpoint for customizable requests is always at the same location:
**yourdomain.com/custom_api/?**  If you don't want to provide customizable API's, you simply provide an empty settings variable in **config.php** by writing `$api_settings = array();`
### Profiles:
API profiles are unique strings provided as a key in `$api_settings`, these are accompanied by a `secret`, which can be `false` or as an API-key (string). 
e.g.:
```
    $api_settings = array(
        'profile1' => array(
            'secret' => false
        ), 
        'profile2' => array(
            'secret' => 'OWP43T3281NNITKZRK4HLRG59KY2L7P2'
        )
    );
```
The example above has two profiles: `profile1` and `profile2`, the first profile is an OPEN API; it can be accessed without knowing the secret. The second profile has a secret key and will only result in a valid API response if the `secret` given as part of the API-request, maches the defined `secret`. As shown in the examples, providing `secret` as part of the request is only required for non-open API's. 
To call these profiles you need the following URIs (we assume the domain is example.org): 
```
example.org/custom_api/?profile=profile1
example.org/custom_api/?profile=profile2&secret=OWP43T3281NNITKZRK4HLRG59KY2L7P2
```
Making abstraction your API-request so far looks like this: 
```
<domain>/custom_api/?profile=<profilename>&secret=<optional_secret>
```

You'd use secret profiles to share data with alligned projects and exchange metadata. The `secret` parameter in the URL is optional for open API's

### Profile settings:
The above configuration does not yet provide any instructions to the backend to generate an API response. To do this, you need to define `requests`. Each profile can have multiple requests; so you don't need to define multiple secrets to exchange data of multiple request types. Each request will return and search for data in a single node, the exception to this is API-requests that include searches in spelling variants. This searchtype will extend it's search variantnodes that are connected to the given core-node.

A core-node is the node where you want to perform a search on, this should match one of the keys you have defined in your nodemodel in the `/config/config.inc.php` file

To demonstrate we'll extend the previous example and assume our nodemodel has nodes called 'Place' and 'Person'. Place-nodes have the following properties: label, local_name, english_name, elevation. Person-nodes have the following properties: first_name, last_name, nationality. Some of this data should be accessible in the open API, other parts of the data will only be accessible for users that know the `secret` key.

```
    $api_settings = array(
        'profile1' => array(
            'secret' => false, 
            'requests' =>array(
                'placedata' => array(
                    'nodelabel' => 'Place',
                    'search_vars' => false,
                    'returns' => array(
                        'properties' => array('label', 'local_name', 'english_name', 'elevation'),
                        'stableURI' => true,
                        'variants' => false
                    )
                ), 
                'persondata' => array(
                    'nodelabel' => 'Person', 
                    'search_vars' => true, 
                    'returns' => array(
                        'properties' => array('first_name'), 
                        'stableURI' => true,
                        'variants' => true
                    )
                )
            )
        ), 
        'profile2' => array(
            'secret' => 'OWP43T3281NNITKZRK4HLRG59KY2L7P2', 
            'requests' =>array(
                'places' => array(
                    'nodelabel' => 'Place',
                    'search_vars' => false,
                    'returns' => array(
                        'properties' => array('label', 'local_name'),
                        'stableURI' => true,
                        'variants' => true
                    )
                ), 
                'people' => array(
                    'nodelabel' => 'Person', 
                    'search_vars' => true, 
                    'returns' => array(
                        'properties' => array('first_name', 'last_name', 'nationality'), 
                        'stableURI' => true,
                        'variants' => true
                    )
                )
            )
        )
    );
```
Let's go through this step by step and name all the individual keys present in a single request. An abstract request looks like this: 
```
'places' => array(
    'nodelabel' => <Nodelabel present in your core configuration file>,
    'search_vars' => <Boolean, does the searchquery cover variant nodes or not?>,
    'returns' => array(
        'properties' => array(<a_node_property>, <a_node_property>),
        'stableURI' => <Boolean; should the request return stable URI's for nodes matching the request>,
        'variants' => <Boolean; should the request return variants linked to the matching nodes>
    )
```
based on the given example scenario: 

`nodelabel` = e.g. 'Person' or 'Place'
`search_vars` = A choice you make; do you want the API to search for entity nodes based on matching variant labels or not. If you set this to True, the variable `search_parameters` has to refer to node properties which are present in Variant nodes, not the Entity nodes.
`nodelabel` = that match variants contraints, or that match node constraints. 
`properties` = the array of properties you want to return. These properties must be defined in your model
`stableURi` = A boolean you set, when set to true the API will return stable URI's for the nodes matching the request.
`variants` = A boolean you set, when set to true the API willr return variants that are linked to the node matching the request. 

### Search settings: 
Each defined profile has it's own search settings: these search settings tell the API what URL-parameters it should use to match against node properties. 

An abstracted search_parameters settings object looks like this: 
```
'search_parameters' => array(
    array(<getParameter>, <node_property>), 
    array(<getParameter>, <node_property>),
    array(<getParameter>, <node_property>)
)
```

As can be seen, `search_parameters` is a nested 2D-array; every subarray is a single parameter you want to look for and holds two string values. The first element is the parameter passed in the URL (GET), the second string is the property as it is encoded in the node. Going back to the previous example a fully configured file holding two requests profiles with two requests types each becomes: 

```
    $api_settings = array(
        'profile1' => array(
            'secret' => false, 
            'requests' =>array(
                'placedata' => array(
                    'nodelabel' => 'Place',
                    'search_vars' => false,
                    'returns' => array(
                        'properties' => array('label', 'local_name', 'english_name', 'elevation'),
                        'stableURI' => true,
                        'variants' => false
                    )
                ), 
                'persondata' => array(
                    'nodelabel' => 'Person', 
                    'search_vars' => true, 
                    'returns' => array(
                        'properties' => array('first_name'), 
                        'stableURI' => true,
                        'variants' => true
                    )
                )
            )
        ), 
        'profile2' => array(
            'secret' => 'OWP43T3281NNITKZRK4HLRG59KY2L7P2', 
            'requests' =>array(
                'places' => array(
                    'nodelabel' => 'Place',
                    'search_vars' => false,
                    'returns' => array(
                        'properties' => array('label', 'local_name'),
                        'stableURI' => true,
                        'variants' => true
                    ), 
                    'search_parameters' => array(
                        array('varlabel', 'variant'), 
                        array('uid', 'uid')
                    )
                ), 
                'people' => array(
                    'nodelabel' => 'Person', 
                    'search_vars' => true, 
                    'returns' => array(
                        'properties' => array('first_name', 'last_name', 'nationality'), 
                        'stableURI' => true,
                        'variants' => true
                    ), 
                    'search_parameters' => array(
                        array('varlabel', 'variant'), 
                        array('uid', 'uid')
                    )
                ), 

            )
        )
    );
```

To create a new requestprofile, you can use the following template, replace all values between `<>` with the appropriate values for your use case. The provided template should be embedded inside the `$api_settings` variable. Remember that new profiles are separated by commas from previous profiles and should have unique names. 
```
'profilename' => array(
    'secret' => <stringsecret>, 
    'requests' =>array(
        '<type>' => array(
            'nodelabel' => <nodelabel>,
            'search_vars' => <bool>,
            'returns' => array(
                'properties' => array(<a_node_property>, <a_node_property>),
                'stableURI' => <bool>,
                'variants' => <bool>
            ), 
            'search_parameters' => array(
                array(<urlparameter>, <nodeproperty>), 
            )
        )
    )
)
```