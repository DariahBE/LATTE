<?php

  class Wikidata_user extends User{

    private $wikidataPrefixToWikipediaLinks = array(
      // add whatever languages you want!
      //  As key you use the key used by the wikidata response.
      //  THe value is an array holding the following options: 
      //      URL PREFIX: the first part is the prefix of the URL (notice the language is encoded in there)
      //      DOM STRING: The second part is the string shown in the DOM to indicate what lanagues are use.  
      //      ISDEFAULT?: A Boolean indicating if the language is shown by default. 
      //
      'enwiki' => array('https://en.wikipedia.org/wiki/', 'English', True), 
      'frwiki' => array('https://fr.wikipedia.org/wiki/', 'Français', True), 
      'nlwiki' => array('https://nl.wikipedia.org/wiki/', 'Nederlands', False), 
      'dewiki' => array('https://de.wikipedia.org/wiki/', 'Deutsch', True), 
      'plwiki' => array('https://pl.wikipedia.org/wiki/', 'Polski', False), 
      'ruwiki' => array('https://ru.wikipedia.org/wiki/', 'русский язык', False), 
      'ptwiki' => array('https://pt.wikipedia.org/wiki/', 'Português', False), 
      'eswiki' => array('https://es.wikipedia.org/wiki/', 'Español', False), 
      'itwiki' => array('https://it.wikipedia.org/wiki/', 'Italiano', False), 
      'elwiki' => array('https://el.wikipedia.org/wiki/', 'ελληνικά', False), 
      'huwiki' => array('https://hu.wikipedia.org/wiki/', 'magyar nyelv', False), 
      'ukwiki' => array('https://uk.wikipedia.org/wiki/', 'українська мова', False), 
    ); 

    private $wikidataProperties = array(
      //Add whatever properties you want!! NOTICE for every added property, set the appropriate data type to use in the frontend (4th parameter) !!!
      // The value is an array, 
      //      THe first part is the label to be used in the DOM: 
      //      The second part is the category the data is part of; JS code should group properties together!! (i.e. put all external identfiers close together....)
      //      The third part is a boolean: True indicates the property is part of the default display. 
      //      The Fourth is the type of data used in the User Frontend: Choose between: geo, uri, img, str

      'P625' => array('Coordinates', 'Geography', True, 'geo'),
      'P18' => array('Image', 'Media', False, 'img'), 
      'P214' => array('VIAF ID', 'Identifier', True, 'uri'), 
      'P244' => array('Library of Congress ID', 'Identifier', False, 'str'), 
      'P1566' => array('Geonames ID', 'Identifier', True, 'uri'), 
      'P21' => array('Gender', 'Biography', True, 'str'), 
      'P569' => array('Date of Birth', 'Biography', True, 'str'), 
      'P106' => array('Occupation', 'Biography', False, 'str'), 
      'P2671' => array('Google Knowledge Graph ID', 'Identifier', False, 'str'), 
    ); 

    //used for the label returned by the wikidata api: 
    private $wikidataPreferedLanguages = array(
      'en' => 'English',
      'fr' => 'Français',
      'de' => 'Deutsch', 
      'nl' => 'Nederlands', 
      'pl' => 'Polski',
      'ru' => 'русский язык', 
      'pt' => 'Português', 
      'es' => 'Español', 
      'it' => 'Italiano', 
      'hu' => 'magyar nyelv', 
      'uk' => 'українська мова',
    );

    public $customPreferences = array(
        'shownProperties' => false,
        'fallbackLanguage' => 'en',
        'preferredLanguage' => false,
        'showWikipediaLinksTo' => false,
        'stringmatchWikipediaTitles' =>false
    ); 

    /**helper function reads and validates data coming from the database. If it matches expectations, data is stored in memory, later it is stored in a cookie! */
  private  function read_validate_and_store_settings($data, $settings){
    if(boolval($data)){
      //data is a comma separated string: 
      $retrievedData = explode(',', $data);
      $saveto = $settings[0];
      $checkAgainst = $settings[1];
      //vallidate that every value in $retrievedData is allowed by the backend!
      $trustedOutput = array(); 
      foreach($retrievedData as $choice){
        if (array_key_exists($choice, $checkAgainst)){
          array_push($trustedOutput, $choice);
        }
      }
      if(boolval(count($trustedOutput))){
        $this->customPreferences[$saveto] = $trustedOutput;
      }
    }else{
      return false;
    }
  }

    public function buildPreferences(){
      /**Preferences dominance order: 
       * There is always a Settings-cookie for wikidata preferences. The content of this cookie is determined by: 
       *    If A cookie is set: the cookie takes precedence over all else!
       *    If NO cookie is set and the user is logged and has valid userpreferences: use the stored, static preferences.
       *    If neither a cookie, nor preferences apply to the session => use defaultvalues. 
      */


  
      //
      //      LOADING COOKIES: VALIDATE THEM!!
      //
      $cookies = array(
        'wd_properties' => array('shownProperties', $this->wikidataProperties),
        'wd_pref_lang' => array('preferredLanguage', $this->wikidataPreferedLanguages),
        'wd_wikilinks' => array('showWikipediaLinksTo', $this->wikidataPrefixToWikipediaLinks), 
        'wd_wikipedia_titles' =>array('stringmatchWikipediaTitles', $this->wikidataPrefixToWikipediaLinks)
      ); 


      foreach($cookies as $cookiename => $cookieHandle){
        if(isset($_COOKIE[$cookiename])){
          $saveto = $cookieHandle[0];
          $checkAgainst = $cookieHandle[1];
          //a wikidata properties: cookie is present. 
          //wd_properties is a string (comma separated list of keys)! In the output, only show the properties that are trusted.
          //if tampered input was provided ==> filter all invalid entries
          //if you end up with an empty list ==> the value stays false and the next step is followed!
          $untrustedCookie = explode(',', $_COOKIE[$cookiename]); 
          $trustedOutput = array(); 
          foreach($untrustedCookie as $choice){
            if (array_key_exists($choice, $checkAgainst)){
              array_push($trustedOutput, $choice);
            }
          }
          if(boolval(count($trustedOutput))){
            $this->customPreferences[$saveto] = $trustedOutput;
          }
        }
      }
      //cookies are parse and checked: look for user preferences if any of the optional items are still false AND a session is set!
      if(!($this->customPreferences['shownProperties'] && $this->customPreferences['preferredLanguage'] && $this->customPreferences['showWikipediaLinksTo'] && $this->customPreferences['stringmatchWikipediaTitles'])){
        if(isset($_SESSION['userid'])){
          $result = $this->client->run('
            MATCH (n:priv_user) 
            WHERE n.userid = $uid 
            RETURN
              n.wd_property_preferences as wd_properties,
              n.wd_wikilink_preferences as wd_wikilinks,
              n.wd_language_preferences as wd_pref_lang, 
              n.wd_titlestring_preferences as wd_wikipedia_titles
            ', array('uid'=>$_SESSION['userid']));
          foreach($result as $row){
            if(!$this->customPreferences['shownProperties']){
              $this->read_validate_and_store_settings($row['wd_properties'], $cookies['wd_properties']);
            }
            if (!$this->customPreferences['preferredLanguage']){
              $this->read_validate_and_store_settings($row['wd_pref_lang'], $cookies['wd_pref_lang']);
            }
            if(!$this->customPreferences['showWikipediaLinksTo']){
              $this->read_validate_and_store_settings($row['wd_wikilinks'], $cookies['wd_wikilinks']);
            }
            if(!$this->customPreferences['stringmatchWikipediaTitles']){
              $this->read_validate_and_store_settings($row['wd_wikipedia_titles'], $cookies['wd_wikipedia_titles']);
            }
          }
        }
      }

      //cookies and static user preferences are checked: Look for whatever is defaulted and set that to items that are still false: 
      if(!($this->customPreferences['shownProperties'] && $this->customPreferences['preferredLanguage'] && $this->customPreferences['showWikipediaLinksTo'] && $this->customPreferences['stringmatchWikipediaTitles'])){
        //properties (P-tags)
        if(!$this->customPreferences['shownProperties']){
          $defaultProperties = array(); 
          foreach($this->wikidataProperties as $k=>$v){
            if($v[2]){
              array_push($defaultProperties, $k); 
            }
          }
          $this->customPreferences['shownProperties'] = $defaultProperties;
        }
        //language: no nonsense - resolve ENglish as default
        if (!$this->customPreferences['preferredLanguage']){
          $this->customPreferences['preferredLanguage'] = array('en');
        }
        //wikipedia links!
        if(!$this->customPreferences['showWikipediaLinksTo']){
          $defaultLinks = array();
          foreach($this->wikidataPrefixToWikipediaLinks as $k=>$v){
            if($v[2]){
              array_push($defaultLinks, $k); 
            }
          }
          $this->customPreferences['showWikipediaLinksTo'] = $defaultLinks;
        }
        //lookup string based matches in the following wikis: A wiki is a 2letter language code followed by 'wiki'
        //e.g. enwiki ==> english Wikipedia; nlwiki, dewiki, frwiki....
        if(!$this->customPreferences['stringmatchWikipediaTitles']){
          $defaultLinks = array();
          foreach($this->wikidataPrefixToWikipediaLinks as $k=>$v){
            if($v[2]){
              array_push($defaultLinks, $k); 
            }
          }
          $this->customPreferences['stringmatchWikipediaTitles'] = $defaultLinks;
        }
      //$customPreferences now has it's settings!
      }
      //end of building preferences is reached ==> store whatever comes out in cookies!
      //this is done every time the method is called, that way unwanted/unsupported properties get filtered out in case of tampering.
      $cleanedCookieString_properties = implode(',', $this->customPreferences['shownProperties']);
      $cleanedCookieString_links = implode(',', $this->customPreferences['showWikipediaLinksTo']);
      $cleanedCookieString_language = implode(',', $this->customPreferences['preferredLanguage']); 
      $cleanedCookieString_titleLookup = implode(',', $this->customPreferences['stringmatchWikipediaTitles']);
      setcookie('wd_properties', $cleanedCookieString_properties, time()+3600*24*365, $path="/");
      setcookie('wd_wikilinks', $cleanedCookieString_links, time()+3600*24*365, $path="/");
      setcookie('wd_pref_lang', $cleanedCookieString_language, time()+3600*24*365, $path="/");
      setcookie('wd_wikipedia_titles', $cleanedCookieString_language, time()+3600*24*365, $path="/");
      }

      private function getUserSettingsForKey($key){
        //no need to check here; input is validated in this->generateForm: 
        $query = 'MATCH (n:priv_user) WHERE n.userid = $uid RETURN '; 
        if($key === 'properties'){
          $query.= ' n.wd_property_preferences ' ;  
        }elseif($key === 'links'){
          $query .= ' n.wd_wikilink_preferences ';
        }elseif($key === 'titles'){
          $query .= ' n.wd_titlestring_preferences ';
        }
        $query .= ' AS data '; 
        $data = $this->client->run($query, array('uid'=>$_SESSION['userid'])); 
        return explode(',', $data[0]['data']);
      }

      public function generateForm($formname){
        if($formname === 'properties'){
          $data = $this->wikidataProperties;
        }elseif($formname === 'links'){
          $data = $this->wikidataPrefixToWikipediaLinks;
        }elseif($formname === 'titles'){
          $data = $this->wikidataPrefixToWikipediaLinks;
        }else{
          throw new Exception('Invalid form');
        }
        //what is chosen by the user: 
        $userChoice = $this->getUserSettingsForKey($formname);
        //generate HTML here: 
        $output = '<div class=""><form method="POST" action="profileUpdate.php" class="grid lg:grid-cols-4 lg:gap-4 md:grid-cols-3 md:gap:3 sm:grid-cols-1 sm:gap-4" >'; 
        foreach($data as $key => $value){
          if(in_array($key, $userChoice)){
            $checked = ' checked '; 
          }else{
            $checked = ''; 
          }
          $output .= 
          '<div class="">
            <input type="checkbox" id="'.$key.'" name="'.$key.'" '.$checked.'  >
            <label for="'.$key.'">'.$value[1].'</label>
          </div>'; 
        }
        $output .= '<input class="hidden" name="form_type_setting_application_value" value="'.$formname.'"><input type="submit" value = "Submit"></form></div>';
        return $output; 
      }


  public function storeProfileSettings($formname, $keys){
    //vallidate the formname: 
    $validForms = array(
      'properties' => array($this->wikidataProperties, 'wd_property_preferences'),
      'links' => array($this->wikidataPrefixToWikipediaLinks, 'wd_wikilink_preferences'), 
      'titles' => array($this->wikidataPrefixToWikipediaLinks, 'wd_titlestring_preferences')
    ); 
    if(!(in_array($formname, array_keys($validForms)))){die("Invalid form detected.");}
    //validate the keys: Do this in such a way that only whitelisted items are approved: 
    $validatedKeys = array(); 
    foreach($keys as $key){
      if(array_key_exists($key, $validForms[$formname][0])){
        array_push($validatedKeys, $key);
      }
    }
    //if one or more valid keys are detected: 
    if(count($validatedKeys)>0){
      //update the settings: 
      $userid = $_SESSION['userid'];
      $validData = implode(',',$validatedKeys); 
      $updateWDsettingsQuery = 'MATCH (n:priv_user) WHERE n.userid = $uid SET n.'.$validForms[$formname][1].' = $prefString  return n; ';
      $updateAction = $this->client->run($updateWDsettingsQuery, array('uid'=>$userid, 'prefString'=>$validData)); 
      if(boolval($updateAction->getSummary()['counters']['propertiesSet'])){
        return True; 
      }
    }
    return False; 
  }

  public function makeSettingsDictionary(){
    //only call this after the buildPreferences() method has been called!
    $outputdictionary = array(
      'fallbackLanguage' => $this->customPreferences['fallbackLanguage'],
    ); 
    $link = array(
      'shownProperties' => array($this->wikidataProperties), 
      'showWikipediaLinksTo' => array($this->wikidataPrefixToWikipediaLinks), 
      'preferredLanguage' => array($this->wikidataPreferedLanguages),
      'stringmatchWikipediaTitles' => array($this->wikidataPrefixToWikipediaLinks)
    );
    foreach($this->customPreferences as $prefName => $prefValue){
      //add the key to the public dictionary if it does not exist!: 
      if(!(array_key_exists($prefName, $outputdictionary))){
        $outputdictionary[$prefName] = array(); 
        //php based dict with settings: 
        $codedSettings = $link[$prefName][0];
        //public dict key is created, now fill with values!
        foreach($prefValue as $choice){
          $valueForChoice = $codedSettings[$choice];
          $outputdictionary[$prefName][$choice] = $valueForChoice; 
        }
        
      }

    }

    return $outputdictionary;
  }

  public function labelIndicator(){
    /**
     * renders a kv-array indicating which property holds the dedicate Q-label for a given entitytype. 
     */
    $indicator = array(); 
    foreach(NODEMODEL as $key => $value){
      foreach (NODEMODEL[$key] as $property => $propsettings){
        if($propsettings[1] === 'wikidata'){
          $indicator[$key] = $property;
        }
      }
    }
    return $indicator;
  }

}
?>