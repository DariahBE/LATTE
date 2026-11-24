<?php

/**
 *  class to generate exports of single text file.
 *    Should be able to set headers of creation page; 
 *    Should be able to verify modes
 *    Should be able to fetch annotations and encode them according to the mode. 
 */

class Exporter {
  protected $client; 
  private $allowedModes; 
  private $mode; 
  private $rawtext; 
  private $identified; 
  private $annotations; 
  private $autoAnnotations; 
  private $breakpoints; 
  private $XMLTaggedText;
  private $collections = array();
  function __construct($client, $mode){
    $this->allowedModes = array('json', 'xml', 'tei', 'webannotation'); 
    $this->client = $client; 
    if(in_array($mode, $this->allowedModes)){
      $this->mode = $mode; 
    }else{
      http_response_code(400);
      die(json_encode(['error' => 'Invalid export mode']));
    }
  }
  
  public function setText($text){
    $this->rawtext = $text; 
  }

  public function setCollections(array $collections){
    $this->collections = $collections;
  }

  public function setIdentifiedText($text){
    $this->identified = $text; 
  }

  public function setAnnotations($annotationArray){
    $this->annotations = $annotationArray; 
    $breakpoints = array(); 
    $annotations = $annotationArray['relations']; 
    foreach($annotations as $key=> $value){
      for($i = $value['start']; $i <= $value['stop']; $i++){
        if(!(array_key_exists($i, $breakpoints))){
          $breakpoints[$i] = array(); 
        }
          $breakpoints[$i][] = $key; 
      }
    }
    $this->breakpoints = $breakpoints; 
  }

  public function setAutoAnnotations($annotationArray){
    $this->autoAnnotations = $annotationArray; 
    //$breakpoints = array();
    foreach($annotationArray as $key=> $value){
      for($i = $value['start']; $i <= $value['stop']; $i++){
        if(!(array_key_exists($i, $this->breakpoints))){
          $this->breakpoints[$i] = array(); 
        }
        $this->breakpoints[$i][] = $value['annotation']; 
      }
    }
  }

  public function outputHeaders($identifier = null){
    $suffix = ($identifier !== null && $identifier !== '') ? ('_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$identifier)) : '';
    if ($this->mode == 'xml'){
      header('Content-Type: text/xml; charset=utf-8');
      header('Content-Disposition: attachment; filename="export' . $suffix . '.xml"');
    }else if($this->mode == 'json'){
      header('Content-Type: application/json; charset=utf-8');
      header('Content-Disposition: attachment; filename="export' . $suffix . '.json"');
    }else if($this->mode == 'tei'){
      header('Content-Type: application/tei+xml; charset=utf-8');
      header('Content-Disposition: attachment; filename="export' . $suffix . '.tei.xml"');
    }else if($this->mode == 'webannotation'){
      header('Content-Type: application/ld+json; charset=utf-8');
      header('Content-Disposition: attachment; filename="export' . $suffix . '.webannotation.jsonld"');
    }
  }

  private function getBaseOrigin(): string {
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $basePath = defined('BASE_URL') ? BASE_URL : '';
    return $scheme . $host . $basePath;
  }

  public function outputContent(){
    $date = date("d-m-Y H:i:s");
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $exportURL = $scheme . $host . ($_SERVER['REQUEST_URI'] ?? '');
    if($this->mode == 'xml'){
      $dom = new DOMDocument();
      $dom->encoding = 'utf-8';
      $dom->xmlVersion = '1.0';
      $dom->formatOutput = true;
      $root = $dom->createElement('Export');
      $metaNode = $dom->createElement('metadata');
      $texNode = $dom->createElement('text'); 
      $annoNode = $dom->createElement('annotatedText'); 
      $linkAnnoNode = $dom->createElement('annotations'); 
      $linkEntityNode = $dom->createElement('entities'); 
      // assign fields to metadata: 
      $metaTimeStamp = $dom->createElement('requestTime', $date); 
      $metaSourceStamp = $dom->createElement( 'requestURI', htmlspecialchars($exportURL, ENT_XML1, 'UTF-8'));
      $metaNode->appendChild($metaTimeStamp); 
      $metaNode->appendChild($metaSourceStamp); 
      if (!empty($this->collections)) {
        $collectionsNode = $dom->createElement('collections');
        foreach ($this->collections as $col) {
          $colElem = $dom->createElement('collection');
          $colElem->setAttribute('id', (string)($col['collection_id'] ?? ''));
          $colElem->setAttribute('uid', (string)($col['uid'] ?? ''));
          $colElem->setAttribute('scope', (string)($col['scope'] ?? ''));
          $colElem->appendChild($dom->createElement('name', htmlspecialchars((string)($col['name'] ?? ''), ENT_XML1, 'UTF-8')));
          $colElem->appendChild($dom->createElement('description', htmlspecialchars((string)($col['description'] ?? ''), ENT_XML1, 'UTF-8')));
          $collectionsNode->appendChild($colElem);
        }
        $metaNode->appendChild($collectionsNode);
      }
      // assign data to text: 
      $rawText = $dom->createElement('rawText', htmlspecialchars($this->rawtext)); 
      $texNode->appendChild($rawText);
      foreach($this->XMLTaggedText as $key => $value){
        $e = $this->XMLTaggedText[$key]; 
        if ($e[1]=='annotation'){
          $elem = $dom->createElement('annotation', htmlspecialchars($e[2]));
          $elemAtr = new DOMAttr('id', $e[3]); 
          $elem->setAttribute('start', $this->annotations['relations'][$e[3]]['start']);
          $elem->setAttribute('stop', $this->annotations['relations'][$e[3]]['stop']);
          $elem->setAttributeNode($elemAtr); 
        }else{
          $elem = $dom->createElement('unmarkedText', htmlspecialchars($e[2]));
        }
        $annoNode->appendChild($elem);
      }
      //adding annotationLinks: 
      foreach($this->annotationToEt as $key => $value){
        $annotationReference = $dom->createElement('annotation'); 
        $annotationID = new DOMAttr('id', $key); 
        $annotationReference->appendChild($annotationID);
        $referencedNode = $dom->createElement('references');
        $referencedNodeLabel = new DOMAttr('label', $value[0]);
        $referencedNodePrimary = new DOMAttr('label', $value[1]);
        $referencedNode->appendChild($referencedNodeLabel);
        $referencedNode->appendChild($referencedNodePrimary);
        $annotationReference->appendChild($referencedNode);
        $linkAnnoNode->appendChild($annotationReference);
      }
      //adding entityLinks: 
      // missing NEO id in entitydict. 
      foreach($this->entityDict as $annotkey => $value){
        $oneEt = $dom->createElement('entity');
        $etAtrid = new DOMAttr('id', $value['primaryKey']['value']); 
        $etURI = new DOMAttr('uri', $value['primaryKey']['URI']); 
        $etLabel = new DOMAttr('label', $value['label']);
        $oneEt->appendChild($etLabel);
        $oneEt->appendChild($etAtrid);
        $oneEt->appendChild($etURI);
        $etLabel = $dom->createElement('URI', $value['primaryKey']['URI']);
        $oneEt->appendChild($etLabel); 
        $etkey = $value['et_neo']; 
        foreach($value['properties'] as $key => $subvalue){
          $prop = $dom->createElement('property');
          $propAtr = new DOMAttr('name', $subvalue['name']); 
          $prop->appendChild($propAtr); 
          $propValue = $dom->createElement('value', $subvalue['value']); 
          $prop->appendChild($propValue); 
          $oneEt->appendChild($prop);
        }
        if (array_key_exists($etkey, $this->varspellings)){
          $variantBox = $dom->createElement('spelling_variants'); 
          if(array_key_exists('labelVariants', $this->varspellings[$etkey])){
            foreach($this->varspellings[$etkey]['labelVariants'] as $showkey => $showvalue){
              $variantRow = $dom->createElement('variant'); 
              $varValue = new DOMAttr('string', $showvalue['value']);
              $varUID = new DOMAttr('uuid', $showvalue['uid']); 
              $variantRow->appendChild($varValue);
              $variantRow->appendChild($varUID);
              $variantBox->appendChild($variantRow); 
            }
          }
          $oneEt->appendChild($variantBox); 
        }
        $linkEntityNode->appendChild($oneEt); 
      }
      $root->appendChild($metaNode);
      $root->appendChild($texNode);
      $root->appendChild($annoNode);
      $root->appendChild($linkAnnoNode);
      $root->appendChild($linkEntityNode);
      $dom->appendChild($root);
      return $dom->saveXML();
    }else if ($this->mode == 'json'){
      $taggedTextArray = array(); 
      foreach($this->XMLTaggedText as $key => $value){
        $taggedTextArray[] = array(
          'type'=>$value[1],
          'content'=>$value[2],
          'id'=>$value[3]
        );
      }
      $annotationLinks = array();
      foreach($this->annotationToEt as $key => $value){
        $relData = $this->annotations['relations'][$key] ?? null;
        $annotationLinks[] = array(
          'annotationId' => $key,
          'referencesLabel' => $value[1], 
          'start' => $relData ? $relData['start'] : null,
          'stop' => $relData ? $relData['stop'] : null

        );
      }
      $entityLinks = array(); 
      foreach($this->entityDict as $key => $value){
        $properties = array(); 
        foreach($value['properties'] as $propkey =>$propvalue){
          $properties[]=$propvalue;
        }

        // new code for variant display
        $etkey = $value['et_neo'];
        $variantBox = array(); 
        if(array_key_exists('labelVariants', $this->varspellings[$etkey])){
          foreach($this->varspellings[$etkey]['labelVariants'] as $showkey => $showvalue){
            $variantRow = array(); 
            $varValue = $showvalue['value'];
            $varUID = $showvalue['uid']; 
            $variantRow[] = array('value'=>$varValue, 'uuid'=>$varUID);
            $variantBox[]=$variantRow; 
          }
        }

        $entityLinks[]  =  array(
          'label' => $value['label'],
          'pk' => $value['primaryKey']['value'],
          'uri' => $value['primaryKey']['URI'],
          'properties' => $properties,
          'variants'=> $variantBox
        );
      }
        
      
      $exportCollections = array_map(function ($col) {
        return array(
          'id' => (int)($col['collection_id'] ?? 0),
          'uid' => (string)($col['uid'] ?? ''),
          'name' => (string)($col['name'] ?? ''),
          'description' => (string)($col['description'] ?? ''),
          'scope' => (string)($col['scope'] ?? ''),
        );
      }, $this->collections);

      $root = array(
        'metadata' => array(
          'requestTime' => $date,
          'requestURI' => $exportURL,
          'collections' => $exportCollections
        ), 
        'text' => array(
          'rawText' => $this->rawtext
        ),
        'annotatedText' => $taggedTextArray, 
        'annotations' => $annotationLinks, 
        'entities' => $entityLinks
      ); 
      return json_encode($root, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); 
    } else if ($this->mode === 'tei') {
      return $this->generateTeiXml();
    } else if ($this->mode === 'webannotation') {
      return $this->generateWebAnnotation();
    }
  }

  public function generateAnnotatedText(){
    $prevtype = False;
    $baseString='';
    $this->XMLTaggedText = []; 
    $blockKey = 0; 
    $forceSwap = False; 
    $prevAnnotationKey = ''; 
    foreach($this->identified as $index=> $character){
      if(array_key_exists($index, $this->breakpoints)){
        //annotation: if $index exists as a breakpoint!
        $type = "annotation";
        $currentAnnotationKey = implode(',', $this->breakpoints[$index]);
        //if two annotation follow each other, or have an overlap, detect it like this:
        if ($prevAnnotationKey != '' && $prevAnnotationKey != $currentAnnotationKey){
          $forceSwap = True;
        }
      }else{
        //text: 
        $type = "text"; 
        $currentAnnotationKey = ''; 
      }
      //when it switches between types or adjacent/overlapping breakpoints: 
      if($prevtype != $type || $forceSwap){
        if($baseString != ''){
          $forceSwap=False;
          $this->XMLTaggedText[$blockKey] = array($blockKey, $prevtype, $baseString, $prevAnnotationKey); 
          $baseString = '';
          $blockKey = $blockKey+1;  
        }
      }
      //always do: 
      $baseString.=$character;
      $prevtype = $type; 
      $prevAnnotationKey = $currentAnnotationKey; 
    }
    //append the very last item!
    $this->XMLTaggedText[$blockKey] = array($blockKey, $prevtype, $baseString, $prevAnnotationKey); 
  }

  public function outputAnnotations($db){
    $neoKeys = array_unique(array_column($this->annotations['relations'],'neoid'));
    $entities = array(); 
    $doneEts = array(); 
    $this->annotationToEt = array();
    $this->varspellings = array(); 
    foreach($this->annotations['relations'] as $keyUID => $valueArr){
      $neoID = $valueArr['neoid'];      
      $data = $db->getAnnotationInfo($neoID);
      $entityLabel = $data['entity']['labels'][0];
      $modelOfEntity = NODEMODEL[$entityLabel];
      $entityPrimaryKey = helper_extractPrimary($entityLabel);
      $entityNeoId = $data['entity_neo_id']; 
      $variants = $db->fetchVariants($entityNeoId); 
      $entityProps = $data['entity']['properties'];
      $primaryKeyValue = null;
      $hasProp = function ($props, string $propKey): bool {
        if ($props instanceof \Laudis\Neo4j\Types\CypherMap) {
          return $props->hasKey($propKey);
        }
        return is_array($props) && array_key_exists($propKey, $props);
      };
      $getProp = function ($props, string $propKey) {
        if ($props instanceof \Laudis\Neo4j\Types\CypherMap) {
          return $props->get($propKey);
        }
        return $props[$propKey] ?? null;
      };
      if ($entityPrimaryKey && $hasProp($entityProps, $entityPrimaryKey)) {
        $candidate = $getProp($entityProps, $entityPrimaryKey);
        if ($candidate !== null && $candidate !== '') {
          $primaryKeyValue = $candidate;
        }
      }
      if ($primaryKeyValue === null) {
        foreach (['uid', 'name', 'label', 'title'] as $fallbackKey) {
          if ($hasProp($entityProps, $fallbackKey)) {
            $candidate = $getProp($entityProps, $fallbackKey);
            if ($candidate !== null && $candidate !== '') {
              $primaryKeyValue = $candidate;
              break;
            }
          }
        }
      }
      if ($primaryKeyValue === null) {
        $primaryKeyValue = (string)$entityNeoId;
      }
      $this->annotationToEt[$keyUID] = array($entityLabel, $primaryKeyValue);
      if (!(in_array($entityLabel.$primaryKeyValue, $doneEts))){
        $stableLink = $this->getBaseOrigin() . '/URI/' . rawurlencode($entityLabel) . '/' . rawurlencode((string)$primaryKeyValue);
        //store in array: 
        $entities[$neoID] = array();
        $entities[$neoID]['label'] = $entityLabel;
        $entities[$neoID]['linkedByAnnotation'] = $keyUID;
        //primary key: 
        $entities[$neoID]['primaryKey'] = array();
        $entities[$neoID]['primaryKey']['name'] = $entityPrimaryKey;
        $entities[$neoID]['primaryKey']['value'] = $primaryKeyValue;
        $entities[$neoID]['primaryKey']['URI'] = $stableLink;
        //other properties: 
        $entities[$neoID]['properties'] = array(); 
        foreach($data['entity']['properties'] as $key => $value){
          if(array_key_exists($key, $modelOfEntity)){
            $entities[$neoID]['properties'][$key] = array(); 
            $entities[$neoID]['properties'][$key]['name'] = $modelOfEntity[$key][0];
            $entities[$neoID]['properties'][$key]['value'] = $value; 
          }
        }
        if (!array_key_exists('wikidata_id', $entities[$neoID]['properties']) && $hasProp($entityProps, 'wikidata_id')) {
          $wdVal = trim((string)$getProp($entityProps, 'wikidata_id'));
          if ($wdVal !== '' && preg_match('/^Q[0-9]+$/', $wdVal)) {
            $entities[$neoID]['properties']['wikidata_id'] = array(
              'name' => 'Wikidata ID',
              'value' => $wdVal
            );
          }
        }
        $entities[$neoID]['et_neo'] = $entityNeoId;
        $this->varspellings[$entityNeoId] = $variants; 
        $doneEts[]=$entityLabel.$primaryKeyValue;
      }

    }
    $this->entityDict = $entities; 
  }

  public function generateTeiXml() {
    $date = date("c");
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $exportURL = $scheme . $host . ($_SERVER['REQUEST_URI'] ?? '');

    $dom = new DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;

    $tei = $dom->createElementNS('http://www.tei-c.org/ns/1.0', 'TEI');
    $dom->appendChild($tei);

    // TeiHeader.
    $teiHeader = $dom->createElement('teiHeader');
    $fileDesc = $dom->createElement('fileDesc');
    $titleStmt = $dom->createElement('titleStmt');
    $title = $dom->createElement('title', 'LATTE text export');
    $titleStmt->appendChild($title);
    $fileDesc->appendChild($titleStmt);

    $publicationStmt = $dom->createElement('publicationStmt');
    $publisher = $dom->createElement('publisher', 'LATTE annotation platform');
    $pubDate = $dom->createElement('date', $date);
    $publicationStmt->appendChild($publisher);
    $publicationStmt->appendChild($pubDate);
    $fileDesc->appendChild($publicationStmt);

    $sourceDesc = $dom->createElement('sourceDesc');
    $pSource = $dom->createElement('p', 'Generated from ' . htmlspecialchars($exportURL, ENT_XML1, 'UTF-8'));
    $sourceDesc->appendChild($pSource);
    $fileDesc->appendChild($sourceDesc);

    if (!empty($this->collections)) {
      $notesStmt = $dom->createElement('notesStmt');
      foreach ($this->collections as $col) {
        $colNote = $dom->createElement('note', htmlspecialchars((string)($col['name'] ?? ''), ENT_XML1, 'UTF-8'));
        $colNote->setAttribute('type', 'collection');
        $colNote->setAttribute('subtype', (string)($col['scope'] ?? ''));
        $colNote->setAttribute('n', (string)($col['collection_id'] ?? ''));
        $colNote->setAttribute('corresp', $this->getBaseOrigin() . '/collection/' . rawurlencode((string)($col['collection_id'] ?? '')));
        $notesStmt->appendChild($colNote);
      }
      $fileDesc->appendChild($notesStmt);
    }

    $teiHeader->appendChild($fileDesc);
    $tei->appendChild($teiHeader);

    // Text body with inline referenced strings.
    $textElem = $dom->createElement('text');
    $body = $dom->createElement('body');
    $pBody = $dom->createElement('p');

    if (!empty($this->XMLTaggedText)) {
      foreach ($this->XMLTaggedText as $segment) {
        $segType = $segment[1] ?? 'text';
        $segContent = $segment[2] ?? '';
        $segAnnoKey = $segment[3] ?? '';

        if ($segType === 'annotation') {
          $rs = $dom->createElement('rs');
          $annoIds = explode(',', $segAnnoKey);
          $firstId = $annoIds[0];

          if (isset($this->annotationToEt[$firstId])) {
            $etType = $this->annotationToEt[$firstId][0];
            $etPk = $this->annotationToEt[$firstId][1];
            $rs->setAttribute('type', $etType);
            $rs->setAttribute('key', $etPk);
            $rs->setAttribute('ref', $this->getBaseOrigin() . '/URI/' . rawurlencode($etType) . '/' . rawurlencode($etPk));
          } else {
            $rs->setAttribute('type', 'unlinked');
          }
          $rs->setAttribute('xml:id', 'anno-' . $firstId);
          $rs->appendChild($dom->createTextNode($segContent));
          $pBody->appendChild($rs);
        } else {
          $pBody->appendChild($dom->createTextNode($segContent));
        }
      }
    } else {
      $pBody->appendChild($dom->createTextNode($this->rawtext ?? ''));
    }

    $body->appendChild($pBody);
    $textElem->appendChild($body);
    $tei->appendChild($textElem);

    // Stand-off entity registry.
    if (!empty($this->entityDict)) {
      $standOff = $dom->createElement('standOff');
      $listPerson = $dom->createElement('listPerson');
      $listPlace = $dom->createElement('listPlace');
      $listOrg = $dom->createElement('listOrg');
      $listGeneric = $dom->createElement('list');

      foreach ($this->entityDict as $ent) {
        $label = $ent['label'] ?? 'Entity';
        $pkVal = $ent['primaryKey']['value'] ?? '';
        $uriVal = $ent['primaryKey']['URI'] ?? '';

        $itemElem = $dom->createElement('item');
        $itemElem->setAttribute('xml:id', 'ent-' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $pkVal));
        $itemElem->setAttribute('corresp', $uriVal);

        $nameElem = $dom->createElement('name', htmlspecialchars($pkVal, ENT_XML1, 'UTF-8'));
        $nameElem->setAttribute('type', $label);
        $itemElem->appendChild($nameElem);

        if (!empty($ent['properties'])) {
          foreach ($ent['properties'] as $prop) {
            $note = $dom->createElement('note', htmlspecialchars((string)$prop['value'], ENT_XML1, 'UTF-8'));
            $note->setAttribute('type', $prop['name']);
            $itemElem->appendChild($note);
          }
        }

        $lowerLabel = strtolower($label);
        if ($lowerLabel === 'person' || strpos($lowerLabel, 'pers') !== false) {
          $personElem = $dom->createElement('person');
          $personElem->setAttribute('xml:id', 'ent-' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $pkVal));
          $persName = $dom->createElement('persName', htmlspecialchars($pkVal, ENT_XML1, 'UTF-8'));
          $personElem->appendChild($persName);
          $listPerson->appendChild($personElem);
        } else if ($lowerLabel === 'place' || $lowerLabel === 'location' || strpos($lowerLabel, 'loc') !== false) {
          $placeElem = $dom->createElement('place');
          $placeElem->setAttribute('xml:id', 'ent-' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $pkVal));
          $placeName = $dom->createElement('placeName', htmlspecialchars($pkVal, ENT_XML1, 'UTF-8'));
          $placeElem->appendChild($placeName);
          $listPlace->appendChild($placeElem);
        } else if ($lowerLabel === 'organization' || $lowerLabel === 'org') {
          $orgElem = $dom->createElement('org');
          $orgElem->setAttribute('xml:id', 'ent-' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $pkVal));
          $orgName = $dom->createElement('orgName', htmlspecialchars($pkVal, ENT_XML1, 'UTF-8'));
          $orgElem->appendChild($orgName);
          $listOrg->appendChild($orgElem);
        } else {
          $listGeneric->appendChild($itemElem);
        }
      }

      if ($listPerson->hasChildNodes()) {
        $standOff->appendChild($listPerson);
      }
      if ($listPlace->hasChildNodes()) {
        $standOff->appendChild($listPlace);
      }
      if ($listOrg->hasChildNodes()) {
        $standOff->appendChild($listOrg);
      }
      if ($listGeneric->hasChildNodes()) {
        $standOff->appendChild($listGeneric);
      }
      if ($standOff->hasChildNodes()) {
        $tei->appendChild($standOff);
      }
    }

    return $dom->saveXML();
  }

  public function generateWebAnnotation() {
    $date = date("c");
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $exportURL = $scheme . $host . ($_SERVER['REQUEST_URI'] ?? '');

    $items = [];
    $rawChars = mb_str_split($this->rawtext ?? '', 1, 'UTF-8');
    $rawLen = count($rawChars);

    if (!empty($this->annotations['relations'])) {
      foreach ($this->annotations['relations'] as $annoId => $rel) {
        $start = (int)$rel['start'];
        $stop = (int)$rel['stop'];

        $exact = '';
        if ($start >= 0 && $start < $rawLen && $stop >= $start) {
          $exactSlice = array_slice($rawChars, $start, $stop - $start + 1);
          $exact = implode('', $exactSlice);
        }

        $body = null;
        if (isset($this->annotationToEt[$annoId])) {
          $etType = $this->annotationToEt[$annoId][0];
          $etPk = $this->annotationToEt[$annoId][1];
          $bodyUri = $this->getBaseOrigin() . '/URI/' . rawurlencode($etType) . '/' . rawurlencode($etPk);

          $bodyProps = [];
          if (!empty($this->entityDict)) {
            foreach ($this->entityDict as $ent) {
              if (($ent['label'] ?? '') === $etType && ($ent['primaryKey']['value'] ?? '') === $etPk) {
                $bodyProps = $ent['properties'] ?? [];
                break;
              }
            }
          }

          $body = [
            'id' => $bodyUri,
            'type' => $etType,
            'label' => $etPk,
            'value' => $etPk,
            'properties' => $bodyProps
          ];
        }

        $annoType = defined('ANNONODE') ? ANNONODE : 'Annotation';
        $item = [
          'id' => $this->getBaseOrigin() . '/URI/' . rawurlencode($annoType) . '/' . rawurlencode((string)$annoId),
          'type' => 'Annotation',
          'motivation' => 'identifying',
          'created' => $date,
          'body' => $body,
          'target' => [
            'source' => $exportURL,
            'selector' => [
              [
                'type' => 'TextPositionSelector',
                'start' => $start,
                'end' => $stop + 1
              ],
              [
                'type' => 'TextQuoteSelector',
                'exact' => $exact
              ]
            ]
          ]
        ];

        $items[] = $item;
      }
    }

    $collection = [
      '@context' => 'http://www.w3.org/ns/anno.jsonld',
      'id' => $exportURL,
      'type' => 'AnnotationCollection',
      'label' => 'LATTE web annotations',
      'generated' => $date,
      'collections' => array_map(function ($col) use ($scheme, $host) {
        return [
          'id' => $this->getBaseOrigin() . '/collection/' . rawurlencode((string)($col['collection_id'] ?? '')),
          'collection_id' => (int)($col['collection_id'] ?? 0),
          'uid' => (string)($col['uid'] ?? ''),
          'name' => (string)($col['name'] ?? ''),
          'scope' => (string)($col['scope'] ?? ''),
        ];
      }, $this->collections),
      'total' => count($items),
      'items' => $items
    ];

    return json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

}

?>