<?php 

// ALWAYS EXCLUDE NODES WITH PRIVATE == TRUE  FROM THE RESULT SET.
//      NOTICE: make the code so that nodes without a private attribue
//              are returned!


class API {
    private $settings; 
    private $profile; 
    private $parameters = array(); //query parameters
    private $ph = 1; //placeholder
    function __construct($settings){
        $this->settings = $settings; 
    }


    public function checkrequestsecret($apiName, $secret){
        /**
         * Checks if:
         *  the APIrequest has a valid name 
         *  the secret matches the requestname
         * Loads the settings as a property!
         */
        if(array_key_exists($apiName, $this->settings)){
            $this->apiName = $apiName; 
            //we're using loosy comparison to catch emtpy strings too
            $api_secret = $this->settings[$apiName]['secret'];
            if($this->settings[$apiName]['secret'] != false){
                //here we use 
                if($api_secret === $secret){
                    $this->profile = $this->settings[$apiName]; 
                    return true; 
                }
            }
        }
        //die by default
        $this->deathByError(); 
    }

    public function deathByError($errmsg = 'Invalid API request. '){
        /**
         * custom error handler
         */
        die(json_encode(
                array('err' => $errmsg)
            )
        ); 
    }

    public function process_ph($var){
        /**
         *  takes a variable value and returns a placeholder 
         *  the variable gets stored in a new associateve array
         *  where it is identified by the placeholder. this 
         *  allows for parameterized queries.
         */
        $ph_name = 'PH_'.$this->ph;

        $this->search_parameters[$ph_name] = $var; 

        $this->ph+=1; 
        return '$'.$ph_name; 

    }


    // public function restrictNodesByNodeLabel($label){
    //     /**
    //     *   takes the node label 
    //      */

    //      $this->labelLimiter = "'$label' in labels(n) "; 
        
    //     //$this->labelLimiter = implode( ' OR ', $labelClauses);  
    // }
    
    public function readrequests($type){
        if(array_key_exists($type, $this->settings[$this->apiName]['requests'])){
            $this->requestType = $type; 
        } else {
            $this->deathByError('Invalid API profile given.');
        }
        $requested_labels = $this->profile['requests'][$type]['nodelabel'];
        //$this->restrictNodesByNodeLabel($requested_labels); 



        $this->includeVariantsAsSearchParameter($this->profile['requests'][$type]['search_vars']); 
    }

    public function buildParameter($nodelabel, $propertyname, $value){
        if($nodelabel === 'Variant'){

        }
    }

    public function restrictNodeByParameters(){
        $this->parameters = array(); 
        $parameters = $this->profile['requests'][$this->requestType]['search_parameters']; 
        //var_dump($parameters);
        // when using search_vars == true : search vor Variant nodes. 
        // when set tot false, search for the entity node!
        $nodeLabel = $this->profile['requests'][$this->requestType]['search_vars']  ? 'Variant' : $this->profile['requests'][$this->requestType]['nodelabel']; 
        $nodeLetter = $nodeLabel === 'Variant' ? 'v' : 'n'; 
        foreach($parameters as $param){
            $as_get = $param[0]; 
            $nodeProp = $param[1]; 
            //BUG: this is still problematic!
            //      there's no way of correclty referencing if the node should apply the properties on n (entity) or v (variant)
            if(isset($_GET[$as_get])){
                $value = $_GET[$as_get]; 
                //You can have an array as part of the get-request or a string.
                // if it is an array, the user provided values are joined by OR
                if(is_array($value)){
                    $orstatement = array();
                    foreach( $value as $val ) {
                        $orstatement[] = $nodeLetter.'.'.$nodeProp.' = '.$this->process_ph($val); 
                    }
                    $this->parameters[] = '('.implode( ' OR ' , $orstatement ).')';    
                }else{
                    $this->parameters[] = $nodeLetter.'.'.$nodeProp.' = '.$this->process_ph($value).''; 
                }
                //var_dump($value); 
            }
        }
    }

    public function includeVariantsAsSearchParameter($hasvars){
        $nlabel = $this->profile['requests'][$this->requestType]['nodelabel'];
        if(!($hasvars)){
            $this->matchStatement = ' MATCH (n:'.$nlabel.') '; 
        }else{
            $this->matchStatement = ' MATCH (n:'.$nlabel.')<-[r:same_as]-(v:Variant) '; 
        }
    }


    public function makeCypherStatement(){
        //protect the private nodes: 
        $this->parameters[] = ' (NOT EXISTS(n.private) OR n.private <> True) '; 
        $query = $this->matchStatement.
        ' WHERE '.implode(" AND ", $this->parameters).
        ' return n; '; 
        $this->query = $query; 
    }

    public function getQuery(){
        return $this->query; 
    }

    public function getParams(){
        return $this->search_parameters; 
    }

    public function vars_required(){
        /**
         *  returns bool: True if variants are required by the api setting profile
         * defuault/else == false
         */
        $x = $this->profile['requests'][$this->requestType]['returns']['variants'] ?? null; 
        if($x === NULL){
            $this->deathByError('Variants flag not defined in the requestprofile.'); 
        }
        return $x;
    }

    public function uri_required(){
        /**
         *  returns bool: True if Stable URI are required by the api setting profile
         * defuault/else == false
         */
        $x = $this->profile['requests'][$this->requestType]['returns']['stableURI'] ?? null; 
        if($x === NULL){
            $this->deathByError('URI flag not defined in the requestprofile.'); 
        }
        return $x;
    }


    public function format_API_response($data, $node){
        $do_vars = $this->vars_required(); 
        $do_uri = $this->uri_required(); 
        $requested_ouput = $this->profile['requests'][$this->requestType]['returns']['properties']; 
        $record = 0; 
        $echodata = array();
        foreach ($data as $key => $noderecord) {
            $rowResult = array(); 
            $nodelabel = $noderecord['n']['labels'][0];
            $neoid = (int)$noderecord['n']['id']; 
            if($do_uri){
                $rowResult['URI'] = $node->generateURI($neoid); 
                //var_dump(); 
            }
            if($do_vars){
                $rowResult['URI'][]=  $node->findVariants($neoid); 
            }
            $echodata[$record]['properties'] = []; 
            foreach ($requested_ouput as $prop) {
                $r = array();
                //todo
                $r['key'] = NODEMODEL[$nodelabel][$prop][0] ?? NULL;
                $r['prop'] = $noderecord['n']["properties"][$prop] ?? NULL;
                $echodata[$record]['properties'][] = $r;
            }
            // format the node properties from the database into node properties that can be read by users. 
            //var_dump($noderecord) ;
            $record = $record + 1; 
        }
        return $echodata;
    }

}