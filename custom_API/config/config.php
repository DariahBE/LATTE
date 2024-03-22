<?php

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
                'nodelabels' => array('Place'),
                'search_vars' => true,
                'returns' => array(
                    'properties' => array(
                        'Place' => array('tmid')
                    ),
                    'stableURI' => true,
                    'variants' => true
                )
            ),
            'person' => array(
                'nodelabels' => array('Person', 'Test'),
                'search_vars' => true,
                'returns' => array(
                    'properties' => array(
                        'Person' => array('label', 'qid'),
                        'Test' => array('highscore', 'minscore')
                    ),
                    'stableUri' => true,
                    'variants' => true
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
?>