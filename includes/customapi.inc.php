<?php 

//testcase:             entitylinker.test/c_api/test2/mqkldjfaigmnqmkldf

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



    public function deathByError($errmsg = 'Invalid API request'){
        die(json_encode(
                array('err' => $errmsg)
            )
        ); 
    }

    public function process_ph($var){
        $ph_name = 'PH_'.$this->ph;

        $this->search_parameters[$ph_name][]= $var; 

        $this->ph+=1; 
        return $ph_name; 

    }


    public function restrictNodesByNodeLabel($labels){
        /**
         * the api allows multiple nodetypes to be included in the
         * config file. Restrict the match by using the labels() 
         * function built in in NEO4J. 
         */
        $labelClauses = array(); 
        foreach ($labels as  $label) {
            $labelClauses[] = "'$label' in labels(n) "; 
        }
        $this->labelLimiter = implode( ' OR ', $labelClauses);  
    }
    
    public function readrequests($type){
        $requested_labels = $this->profile['requests'][$type]['nodelabels'];
        $this->restrictNodesByNodeLabel($requested_labels); 
        $this->includeVariantsAsSearchParameter($this->profile['requests'][$type]['search_vars']); 
    }


    public function restrictNodetypeByParameters($type, $parameters){
        foreach($parameters as $param){
            if(isset($_GET[$type."_".$param])){
                $value = $_GET[$type."_".$param]; 
                $this->parameters[] = 'n:'.$type.' and n.'.$parameter.' = $value'; 
            }
        }
    }

    public function includeVariantsAsSearchParameter($hasvars){
        if(!($hasvars)){
            $this->variantInclusion = ' '; 
        }else{
            $this->variantInclusion = ' OPTIONAL MATCH (n)<-[r:same_as]-(v:Variant) '; 
            //read varlabel from GET requests!
            $var = $_GET['varlabel']; 
            if (is_string($var)){
                //you only have a single variant label provided by the get request
                $this->parameters[] = ' v.variant = '.$this->process_ph($var); 
            }else{
                //var is a list with variantlabels inside: varlabel[]=hello&varlabel[]=world
                foreach($var as $variant){
                    $this->parameters[] = ' v.variant = '.$this->process_ph($variant); 
                }
            }
            var_dump($var); 
        }
    }


    public function makeCypherStatement(){
        $query = 'MATCH (n) 
        '.$this->variantInclusion.' 
        WHERE ('. $this->labelLimiter .") AND ". 
        implode(' OR ', $this->parameters).


        ' return n; '; 

        echo $query; 
    }
}