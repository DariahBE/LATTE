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
      //      The second part is a boolean: True indicates the property is part of the default display. 
      //      The third is the type of data used in the User Frontend: Choose between: geo, uri, img, str

      'P625' => array('Coordinates', True, 'geo'),
      'P18' => array('Image', True, 'img'), 
      'P94' => array('Coat of Arms', False, 'img'), 
      'P1801' => array('Commemorative Plaque', False, 'img'), 
      'P1082' => array('Population count', False, 'str'), 
      'P158' => array('Seal', False, 'img'), 
      'P214' => array('VIAF ID', True, 'uri'), 
      'P244' => array('Library of Congress ID', False, 'uri'), 
      'P1566' => array('Geonames ID', True, 'uri'), 
      'P21' => array('Gender', True, 'str'), 
      'P569' => array('Date of Birth', True, 'str'), 
      'P106' => array('Occupation', False, 'str'), 
      'P2671' => array('Google Knowledge Graph ID', False, 'uri'), 
      'P1449' => array('Nickname', True, 'str'),
      'P1198' => array('Unemployment rate', True, 'str'),
      'P2884' => array('Mains voltage', True, 'str'), 
      'P2250' => array('Life expectansy', True, 'str'), 
      'P2044' => array('Height above sea level', True, 'str')
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
          $query = "SELECT 
            userdata.wd_property_preferences as wd_properties,
            userdata.wd_language_preferences as wd_pref_lang,
            userdata.wd_wikilink_preferences as wd_wikilinks, 
            userdata.wd_titlestring_preferences as wd_wikipedia_titles
            FROM userdata 
            WHERE 
            userdata.id = ? LIMIT 1;
            ";
          $stmt = $this->sqlite->prepare($query);
          $stmt->execute(array($_SESSION['userid']));
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            if($v[1]){
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
      //this is done every time the method is called, that way unwanted/unsuppor²ted properties get filtered out in case of tampering.
      $cleanedCookieString_properties = implode(',', $this->customPreferences['shownProperties']);
      $cleanedCookieString_links = implode(',', $this->customPreferences['showWikipediaLinksTo']);
      $cleanedCookieString_language = implode(',', $this->customPreferences['preferredLanguage']); 
      $cleanedCookieString_titleLookup = implode(',', $this->customPreferences['stringmatchWikipediaTitles']);
      setcookie('wd_properties', $cleanedCookieString_properties, time()+3600*24*365, "/");
      setcookie('wd_wikilinks', $cleanedCookieString_links, time()+3600*24*365, "/");
      setcookie('wd_pref_lang', $cleanedCookieString_language, time()+3600*24*365, "/");
      setcookie('wd_wikipedia_titles', $cleanedCookieString_language, time()+3600*24*365, "/");
      }

      private function getUserSettingsForKey($key){
        //no need to check here; input is validated in this->generateForm: 
        $query = 'SELECT '; 
        //$query = 'MATCH (n:priv_user) WHERE n.userid = $uid RETURN '; 
        if($key === 'properties'){
          //$query.= ' n.wd_property_preferences ' ;  
          $query.= ' userdata.wd_property_preferences ' ;  
        }elseif($key === 'links'){
          //$query .= ' n.wd_wikilink_preferences ';
          $query .= ' userdata.wd_wikilink_preferences ';
        }elseif($key === 'titles'){
          //$query .= ' n.wd_titlestring_preferences ';
          $query .= ' userdata.wd_titlestring_preferences ';
        }elseif($key === 'language'){
          //$query .= ' n.wd_language_preferences ';
          $query .= ' userdata.wd_language_preferences '; 
        }
        $query .= ' AS data FROM userdata WHERE userdata.id = ? LIMIT 1 '; 
        $stmt = $this->sqlite->prepare($query);
        $stmt->execute(array($_SESSION['userid']));
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //$data = $this->client->run($query, array($_SESSION['userid'])); 
        return explode(',', $data[0]['data']);
      }

      public function generateForm($formname){
        if($formname === 'properties'){
          $data = $this->wikidataProperties;
          $position = 0;
        }elseif($formname === 'links'){
          $data = $this->wikidataPrefixToWikipediaLinks;
          $position = 1;
        }elseif($formname === 'titles'){
          $data = $this->wikidataPrefixToWikipediaLinks;
          $position = 1;
        }else{
          throw new Exception('Invalid form');
        }
        //what is chosen by the user: 
        //idSalt: give the ID an extra salt to prevent one form activating the other when clicking on labels: 
        $salt = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8);
        $userChoice = $this->getUserSettingsForKey($formname);
        //generate HTML here: 
        $output = '<div class=""><form method="POST" action="profileUpdate.php" class="" ><div class="grid lg:grid-cols-4 lg:gap-4 md:grid-cols-3 md:gap:3 sm:grid-cols-1 sm:gap-4">'; 
        foreach($data as $key => $value){
          if(in_array($key, $userChoice)){
            $checked = ' checked '; 
          }else{
            $checked = ''; 
          }
          $saltedKey = $salt.$key; //randomizes ID's with salted string
          $output .= 
          '<div class="">
            <input type="checkbox" id="'.$saltedKey.'" name="'.$key.'" '.$checked.'  >
            <label for="'.$saltedKey.'">'.$value[$position].'</label>
          </div>'; 
        }
        $output .= '</div><div class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4 mb-3"><input class="hidden" name="form_type_setting_application_value" value="'.$formname.'"><input type="submit" value = "Submit"></form></div></div>';
        return $output; 
      }


  //BUG Saving new preferences doesnt work yet!
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
      //TODO: update new user data model!
    if(count($validatedKeys)>0){
      //update the settings: 
      $userid = $_SESSION['userid'];
      $validData = implode(',',$validatedKeys); 
      $updateWDsettingsQuery = 'UPDATE userdata SET userdata.'.$validForms[$formname][1].' = ?  WHERE userdata.id = ? LIMIT 1 ';
      //$updateWDsettingsQuery = 'MATCH (n:priv_user) WHERE n.userid = $uid SET n.'.$validForms[$formname][1].' = $prefString  return n; ';
      //$updateAction = $this->client->run($updateWDsettingsQuery, array('uid'=>$userid, 'prefString'=>$validData)); 
      var_dump($updateWDsettingsQuery);
      var_dump(array($validData, $userid)); 
      $stmt = $this->sqlite->prepare($updateWDsettingsQuery);
      $stmt->execute(array($validData, $userid));
      //$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if($stmt->rowCount()===1){
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