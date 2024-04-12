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
    ),
    'test3' => array(
        'secret' => False, 
        'requests' => array(
            'locations' => array(
                'nodelabel' => 'Place',
                'search_vars' => True,
                'returns' => array(
                    'properties' => array('wikidata'),
                    'stableURI' => True,
                    'variants' => True
                ), 
                'search_parameters' => array(
                    array('name', 'variant'), 
                )
            )
        )
    )

);

//DO NOT EDIT BELOW THIS LINE