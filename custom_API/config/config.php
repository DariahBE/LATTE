<?php

//DO NOT EDIT ABOVE THIS LINE

$api_settings = array(
    'test1' => array(
        'secret' => false,
        'requests' => array(
            '' => ''
        )
    ),
    'test2' => array(
        'secret' => 'mqkldjfaigmnqmkldf',
        'requests' => array(
            'place' => array(
                'nodelabel' => 'Place',
                'search_vars' => true,
                'returns' => array(
                    'properties' => array('geoid', 'label'),
                    'stableURI' => true,
                    'variants' => true
                ), 
                'search_parameters' => array(
                    //      GET            DB
                    array('varlabel', 'variant'), 
                    array('uid', 'uid')
                )
            ),
            'person' => array(
                'nodelabel' => 'Person',
                'search_vars' => false,
                'returns' => array(
                    'properties' => array('label', 'qid'),
                    'stableURI' => true,
                    'variants' => true
                ), 
                'search_parameters' => array(
                    array('name', 'label'), 
                    array('gender', 'sex')
                )
            )
        )
    )
);

/**             DOCS: 
 * $api_settings is a nested dict. The KEYS in this dict are:
 *      UNIQUE STRINGS
 *      Should be passed to the API-endpoint as part of the GET request
 *      These keys are to be interpreted as an API-profile
 * 
 * API-profiles:
 *  Each API profile has a key 'secret', this can be False, for API's that are completely open
 *  or can be set to a stringvalue; the stringvalue is the secret key used for a custom API
 * 
*/

//DO NOT EDIT BELOW THIS LINE