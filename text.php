<?php
//include_once('includes/getnode.inc.php');
if (!defined('ROOT_DIR')) {
  include_once(__DIR__.'/config/config.inc.php');
}
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/wikidata_user_prefs.inc.php');
include_once(ROOT_DIR.'/includes/multibyte_iter.inc.php');
include_once(ROOT_DIR.'/includes/annotation.inc.php');
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/collection.inc.php');

$node = new Node($client);


if(isset($_GET['texid'])){
  $propId = $_GET['texid'];
  $nodeType = TEXNODE;
  $propKey = helper_extractPrimary($nodeType);
  //$propKey = PRIMARIES[$nodeType];
  //cast the propID to int if type is set:
  $typeOfId = NODEMODEL[$nodeType][$propKey][1];
  if($typeOfId === "int"){
    $propId = (int)$propId;
  }
}else{
  //load the first text OR error out
  // if we get a returned value from our method ==> load it. 
  header('Location: /error.php?type=textmissing');
  die();
}
//die($propId); 

if($propId == -1){
  //load the first text OR error out
  // if we get a returned value from our method ==> load it. 
  $firstText = $node->getFirstText();
  if($firstText !== false){
      header("Location: /text/$firstText");
  }else{
    //else: DB holds no texts ==> error out: 
      header('Location: /error.php?type=notexts');
    }
    die();
  }

$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC);
$adminMode = $user->isAdmin(); 
$annotation = new Annotation($client);
$wikidata = new Wikidata_user($client);

//checkSession returns: the session['userid'] value
// which is the SQL id!!!
$user_id = $user->checkSession();


$wikidata->buildPreferences();
$text = $node->matchSingleNode($nodeType, $propKey, $propId);
if(!boolval($text) or !array_key_exists('coreID', $text)){
  header('Location: /error.php?type=text&id='.$propId);
  die();
}
$nodeId = $text['coreID'];
$neoId = $text['neoID'];  
// pass the user ID! NOT the UUID!
//send SQL ID of the user! (OK)
$existingAnnotation = $annotation->getExistingAnnotationsInText($neoId, $user_id);
$unlinkedAnnotations = $annotation->getUnlinkedAnnotationsInText($neoId); 
$rejectedAnnotations = $annotation->getRejectedAnnotationsInText($neoId);

// Check if persistent suggestions are stored on the text node itself.
$textProperties = $text['data'][0]->first()['node']['properties']->toArray();
if (!empty($textProperties['latte_suggestions'])) {
  $rawSugs = $textProperties['latte_suggestions'];
  $parsedSugs = is_string($rawSugs) ? json_decode($rawSugs, true) : (is_array($rawSugs) ? $rawSugs : []);
  if (is_array($parsedSugs)) {
    $unlinkedAnnotations = [];
    foreach ($parsedSugs as $sug) {
      $unlinkedAnnotations[] = [
        'annotation' => $sug['id'] ?? ($sug['annotation'] ?? ''),
        'start' => (int)($sug['start'] ?? ($sug['startPos'] ?? 0)),
        'stop' => (int)($sug['stop'] ?? ($sug['endPos'] ?? 0)),
        'label' => $sug['label'] ?? ($sug['labelTex'] ?? ''),
        'text' => $sug['text'] ?? ''
      ];
    }
  }
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars((new CsrfTokenManager())->getToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- <script src="/JS/initiate.js"></script>  autoload of language and extractor, don't do this -->
    <script src="/JS/getLang.js"></script>
    <script src="/JS/getEntities.js?v=<?php echo filemtime(__DIR__ . '/JS/getEntities.js'); ?>"></script>
    <script src="/JS/getEntityInfo.js"></script>
    <script src="/JS/selectInText.js?v=<?php echo filemtime(__DIR__ . '/JS/selectInText.js'); ?>"></script>
    <script src="/JS/showStoredAnnotations.js"></script>
    <script src="/JS/interactWithEntities.js?v=<?php echo filemtime(__DIR__ . '/JS/interactWithEntities.js'); ?>"></script>
    <script src="/JS/latte_annotations.js?v=<?php echo filemtime(__DIR__ . '/JS/latte_annotations.js'); ?>"></script>
    <script src="/JS/et_variants.js"></script>
    <script src="/JS/knowledgebase.js"></script>
    <!-- wikidata SDK and custom code! SDK docs: https://github.com/maxlath/wikibase-sdk-->
    <script src="/JS/wikidata_sdk/wikibase-sdk.js"></script>
    <script src="/JS/wikidata_sdk/wikidata-sdk.js"></script>
    <script src="/JS/wikidata.js"></script>
    <!-- extra script for wikidata content: -->
    <script src="/JS/caroussel.js"></script>
    <script src="/JS/makeMap.js"></script>
    <script src="/JS/leaflet/leaflet.js"></script>
    <script src="/JS/wikidata_prompt.js"></script>
    <!-- datatype validators.  -->
    <script src="/JS/validation.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css?v=<?php echo filemtime(__DIR__ . '/CSS/style_entities.css'); ?>">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="/CSS/leaflet/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen flex flex-col">
  <?php
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();

    // Extract text metadata for document header
    $docTitle = !empty($textProperties['publication']) 
      ? $textProperties['publication'] 
      : (!empty($textProperties['place']) ? $textProperties['place'] : ('Text #' . (int)$propId));
    $docLang = $textProperties['language'] ?? ($textProperties['latte_language'] ?? null);
    $textString = $textProperties[TEXNODETEXT] ?? '';
    $docCharCount = mb_strlen($textString, 'UTF-8');
    $linkedCount = count($existingAnnotation['relations'] ?? []);
    $unlinkedCount = count($unlinkedAnnotations ?? []);

    $collectionManager = new CollectionManager($client);
    $userNeoIdForCol = $user->neoId ? (int)$user->neoId : null;
    $activeCollectionParam = isset($_GET['collection']) ? trim((string)$_GET['collection']) : '';
    $collectionNav = ($activeCollectionParam !== '')
      ? $collectionManager->getCollectionNavigation($activeCollectionParam, (int)$propId, $userNeoIdForCol, TEXTSAREPUBLIC)
      : null;

    if ($collectionNav !== null) {
      $firstText = $collectionNav['first'];
      $prevText = $collectionNav['prev'];
      $nextText = $collectionNav['next'];
      $lastText = $collectionNav['last'];
      $collectionQuerySuffix = '?collection=' . urlencode((string)$collectionNav['collection']['collection_id']);
    } else {
      $firstText = $node->getFirstText();
      $prevText = $node->getPreviousText($propId);
      $nextText = $node->getNextText($propId);
      $lastText = $node->getLastText();
      $collectionQuerySuffix = '';
    }

    $allTextCollections = $collectionManager->getCollectionsForText((int)$neoId, $userNeoIdForCol, TEXTSAREPUBLIC, $user);
    $memberCollections = array_values(array_filter($allTextCollections, function ($col) {
      return !empty($col['is_member']);
    }));
    $editableCollections = array_values(array_filter($allTextCollections, function ($col) {
      return !empty($col['can_edit_membership']);
    }));
    $canCreatePersonalCol = $collectionManager->canCreatePersonalCollection($user);
  ?>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-grow">
    <?php if ($collectionNav !== null): ?>
    <div class="mb-4 px-4 py-2.5 rounded-xl bg-indigo-50 border border-indigo-200 flex flex-wrap items-center justify-between gap-3 text-xs text-indigo-900">
      <div class="flex items-center gap-2">
        <i class="fas fa-layer-group text-indigo-600"></i>
        <span>
          Browsing collection:
          <a href="/collection/<?php echo (int)$collectionNav['collection']['collection_id']; ?>" class="font-bold hover:underline">
            <?php echo htmlspecialchars($collectionNav['collection']['name'], ENT_QUOTES, 'UTF-8'); ?>
          </a>
          <span class="text-indigo-600 ml-1">(<?php echo (int)$collectionNav['position']; ?> of <?php echo (int)$collectionNav['total']; ?>)</span>
        </span>
      </div>
      <div class="flex items-center gap-3">
        <a href="/collection/<?php echo (int)$collectionNav['collection']['collection_id']; ?>" class="font-semibold text-indigo-700 hover:underline">View collection</a>
        <a href="/text/<?php echo (int)$propId; ?>" class="font-semibold text-slate-600 hover:text-slate-900 hover:underline">Exit collection</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Document header banner & navigation bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 mb-8 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold shadow-2xs">
          <i class="fas fa-file-alt"></i>
        </div>
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars($docTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
              Text ID: <?php echo (int)$propId; ?>
            </span>
            <?php if ($docLang): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
              <?php echo htmlspecialchars($docLang, ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php endif; ?>
          </div>
          <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-3">
            <span><i class="fas fa-font text-slate-400 mr-1"></i><?php echo number_format($docCharCount); ?> characters</span>
            <span><i class="fas fa-tags text-slate-400 mr-1"></i><?php echo $linkedCount; ?> linked annotations</span>
            <?php if ($unlinkedCount > 0): ?>
            <span id="headerAutomatedCandidates">
              <i class="fas fa-magic text-slate-400 mr-1"></i><span id="headerAutomatedCandidatesCount"><?php echo $unlinkedCount; ?></span> automated candidates
              <?php if ($user->hasEditRights($user->myRole) >= 1): ?>
                <button type="button" onclick="clearAutomaticAnnotations()" class="ml-1 text-xs text-rose-600 hover:text-rose-800 underline font-semibold cursor-pointer">
                  (Clear)
                </button>
              <?php endif; ?>
            </span>
            <?php endif; ?>
          </p>
          <!-- Collections overview & quick assignment -->
          <div class="mt-2.5 flex flex-wrap items-center gap-1.5 relative">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mr-1">Collections:</span>
            <div id="textCollectionBadges" class="flex flex-wrap items-center gap-1.5">
              <?php if (empty($memberCollections)): ?>
                <span class="text-xs text-slate-400 italic" id="noCollectionsLabel">None</span>
              <?php else: ?>
                <?php foreach ($memberCollections as $col): ?>
                  <?php $isProjCol = ($col['scope'] === 'project'); ?>
                  <a href="/collection/<?php echo (int)$col['collection_id']; ?>"
                     class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors <?php echo $isProjCol ? 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'; ?>">
                    <i class="fas <?php echo $isProjCol ? 'fa-layer-group' : 'fa-bookmark'; ?> text-[10px]"></i>
                    <span><?php echo htmlspecialchars($col['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <?php if ($canCreatePersonalCol || !empty($editableCollections)): ?>
              <button type="button" id="toggleCollectionPopoverBtn"
                      onclick="toggleTextCollectionPopover()"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Manage</span>
              </button>
              <div id="textCollectionPopover" class="hidden absolute left-0 top-full mt-2 w-72 bg-white border border-slate-200 rounded-xl shadow-xl z-40 p-3 space-y-2.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                  <span class="text-xs font-bold text-slate-800">Assign to collections</span>
                  <button type="button" onclick="toggleTextCollectionPopover(false)" class="text-slate-400 hover:text-slate-700 text-xs cursor-pointer">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <div id="textCollectionChecklist" class="max-h-48 overflow-y-auto space-y-1.5">
                  <?php if (empty($editableCollections)): ?>
                    <p class="text-xs text-slate-400">No editable collections yet.</p>
                  <?php else: ?>
                    <?php foreach ($editableCollections as $col): ?>
                      <label class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-xs">
                        <span class="flex items-center gap-2 min-w-0">
                          <input type="checkbox"
                                 <?php echo $col['is_member'] ? 'checked' : ''; ?>
                                 onchange="toggleTextCollectionMembership(<?php echo (int)$col['collection_id']; ?>, this.checked)"
                                 class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                          <span class="truncate font-medium text-slate-700"><?php echo htmlspecialchars($col['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded <?php echo $col['scope'] === 'project' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700'; ?>">
                          <?php echo $col['scope'] === 'project' ? 'Project' : 'Personal'; ?>
                        </span>
                      </label>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
                <?php if ($canCreatePersonalCol): ?>
                <form onsubmit="createQuickPersonalCollection(event)" class="border-t border-slate-100 pt-2 flex items-center gap-1.5">
                  <input type="text" id="quickCollectionName" placeholder="New personal collection..." maxlength="120"
                         class="flex-1 min-w-0 px-2.5 py-1 text-xs border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500">
                  <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer">Add</button>
                </form>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Text navigation buttons -->
      <div class="flex items-center gap-1.5 sm:gap-2">
        <?php if ($firstText === false || $firstText === $propId): ?>
        <span aria-disabled="true"
              class="opacity-40 pointer-events-none inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-2xs"
              title="First text">
          <i class="fas fa-angle-double-left"></i>
        </span>
        <?php else: ?>
        <a href="/text/<?php echo $firstText . $collectionQuerySuffix; ?>"
           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition-colors shadow-2xs"
           title="First text">
          <i class="fas fa-angle-double-left"></i>
        </a>
        <?php endif; ?>

        <?php if ($prevText === false): ?>
        <span id="prevTextBtn"
              aria-disabled="true"
              class="opacity-40 pointer-events-none inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-2xs"
              title="Previous text (Left arrow key)">
          <i class="fas fa-chevron-left text-[11px]"></i>
          <span class="hidden sm:inline">Previous</span>
        </span>
        <?php else: ?>
        <a href="/text/<?php echo $prevText . $collectionQuerySuffix; ?>"
           id="prevTextBtn"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition-colors shadow-2xs"
           title="Previous text (Left arrow key)">
          <i class="fas fa-chevron-left text-[11px]"></i>
          <span class="hidden sm:inline">Previous</span>
        </a>
        <?php endif; ?>

        <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold">
          #<?php echo (int)$propId; ?>
        </span>

        <?php if ($nextText === false): ?>
        <span id="nextTextBtn"
              aria-disabled="true"
              class="opacity-40 pointer-events-none inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-2xs"
              title="Next text (Right arrow key)">
          <span class="hidden sm:inline">Next</span>
          <i class="fas fa-chevron-right text-[11px]"></i>
        </span>
        <?php else: ?>
        <a href="/text/<?php echo $nextText . $collectionQuerySuffix; ?>"
           id="nextTextBtn"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition-colors shadow-2xs"
           title="Next text (Right arrow key)">
          <span class="hidden sm:inline">Next</span>
          <i class="fas fa-chevron-right text-[11px]"></i>
        </a>
        <?php endif; ?>

        <?php if ($lastText === false || $lastText === $propId): ?>
        <span aria-disabled="true"
              class="opacity-40 pointer-events-none inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-2xs"
              title="Last text">
          <i class="fas fa-angle-double-right"></i>
        </span>
        <?php else: ?>
        <a href="/text/<?php echo $lastText . $collectionQuerySuffix; ?>"
           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition-colors shadow-2xs"
           title="Last text">
          <i class="fas fa-angle-double-right"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Workspace grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      <!-- Main reader area -->
      <div class="lg:col-span-8 w-full" id="leftMainPanel">
        <!-- Export toolbar card -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs px-5 py-3 mb-4 flex flex-wrap items-center justify-between gap-3" id="exportBox">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 mr-1">
              <i class="fas fa-file-export text-slate-400"></i>
              <span>Export:</span>
            </span>
            <a class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 text-xs font-semibold transition shadow-2xs"
               title="Export TEI-XML standard"
               href="/export.php?mode=tei&neoid=<?php echo (int)$neoId; ?>">
              <i class="fas fa-code text-[11px]"></i>
              <span>TEI-XML</span>
            </a>
            <a class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-semibold transition shadow-2xs"
               title="Export W3C Web Annotation (JSON-LD)"
               href="/export.php?mode=webannotation&neoid=<?php echo (int)$neoId; ?>">
              <i class="fas fa-tags text-[11px]"></i>
              <span>W3C WebAnno</span>
            </a>
            <a class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold transition shadow-2xs"
               title="Export raw XML"
               href="/export.php?mode=xml&neoid=<?php echo (int)$neoId; ?>">
              <i class="fas fa-file-code text-[11px]"></i>
              <span>XML</span>
            </a>
            <a class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-semibold transition shadow-2xs"
               title="Export graph JSON"
               href="/export.php?mode=json&neoid=<?php echo (int)$neoId; ?>">
              <i class="fas fa-brackets-curly text-[11px]"></i>
              <span>JSON</span>
            </a>
          </div>
          <div class="text-[11px] text-slate-400 hidden sm:block">
            Drag to annotate or click highlights to inspect
          </div>
        </div>

        <!-- Reader canvas card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-10 mb-8 subbox leftsubbox">
          <div id="textcontent" class="select-text">
          <?php
            if(array_key_exists(TEXNODETEXT, $text['data'][0]->first()['node']['properties']->toArray())){
              $textString = $text['data'][0]->first()['node']['properties'][TEXNODETEXT];
              $i = 0;
              $htmlBuffer = '';
              foreach(new MbStrIterator($textString) as $c) {
                $htmlBuffer .= "<span class='ltr' data-itercounter=$i>".nl2br(htmlspecialchars($c, ENT_QUOTES, 'UTF-8'))."</span>";
                $i++;
                if ($i % 2000 === 0) {
                  echo $htmlBuffer;
                  $htmlBuffer = '';
                }
              }
              if ($htmlBuffer !== '') {
                echo $htmlBuffer;
              }
              
              $mandatedCoreNames = array_keys(ConfigModelManager::getMandatedCoreEntities());
              $coreNodeFiltered = array(); 
              foreach(array_keys(CORENODES) as $cn){
                if(!in_array($cn, $mandatedCoreNames, true) && $cn !== TEXNODE && $cn !== ANNONODE){
                  $coreNodeFiltered[]=$cn; 
                }
              }
              $nonCoreEntityTypes = ConfigModelManager::getNonCoreEntityTypes();
              $hasNonCoreEntities = (count($nonCoreEntityTypes) > 0);
            }
          ?>
          </div>
        </div>

        <script>
          var coreNodes = <?php echo json_encode($coreNodeFiltered); ?>;
          var nonCoreEntityTypes = <?php echo json_encode($nonCoreEntityTypes); ?>;
          var hasNonCoreEntities = <?php echo json_encode($hasNonCoreEntities); ?>;
          window.hasNonCoreEntities = hasNonCoreEntities;
          window.nonCoreEntityTypes = nonCoreEntityTypes;
          window.userIsAdmin = <?php echo json_encode($adminMode); ?>;
          window.userHasEditRights = <?php echo json_encode($user->hasEditRights($user->myRole) >= 1); ?>;
          var annocoreNode = <?php echo json_encode(ANNONODE); ?>;
          var languageOptions = {
            'text': <?php echo json_encode($textString)?>,
            'ISO_code': <?php echo json_encode($docLang ?: false)?>,
            'textid': <?php echo json_encode((int)$propId)?>,
            'nodeid': <?php echo json_encode((int)$neoId)?>
          };
          var wdProperties = <?php echo json_encode($wikidata->makeSettingsDictionary()); ?>;
          let startcode = "<?php echo ANNOSTART; ?>";
          let stopcode = "<?php echo ANNOSTOP; ?>";
        </script>
        <style>
          <?php
            helper_parseEntityStyle();
          ?>
        </style>
      </div>

      <!-- Right sidebar panel -->
      <div class="lg:col-span-4 w-full space-y-6 lg:sticky lg:top-20 right" id="rightMainPanel">
        <!-- Entity legend & active document metrics -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <i class="fas fa-layer-group text-indigo-500"></i>
              <span>Entity types</span>
            </h3>
            <span class="text-[11px] font-semibold text-slate-400">Legend</span>
          </div>
          <div class="flex flex-wrap gap-2">
            <?php foreach (CORENODES as $typeKey => $colorVal): ?>
              <?php if ($typeKey !== TEXNODE): ?>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-700 bg-slate-50 border border-slate-200/80 shadow-2xs">
                <span class="w-3 h-3 rounded-full shrink-0" style="background-color: <?php echo htmlspecialchars($colorVal, ENT_QUOTES, 'UTF-8'); ?>;"></span>
                <span><?php echo htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8'); ?></span>
              </span>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php if (boolval(NERCOLOR)): ?>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-700 bg-slate-50 border border-slate-200/80 shadow-2xs">
                <span class="w-3 h-3 rounded-full shrink-0" style="background-color: <?php echo htmlspecialchars(NERCOLOR, ENT_QUOTES, 'UTF-8'); ?>;"></span>
                <span>Automatic NER</span>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Entity lookup & fuzzy matching options -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 meta" id="topmeta">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <img src="/images/wikidatawiki_small.png" alt="Wikidata" class="w-4 h-4 object-contain">
              <span>Entity lookup options</span>
            </h3>
          </div>

          <div id="wdoptionsblock" class="space-y-3">
            <div>
              <label for="wdlookuplanguage" class="block text-xs font-medium text-slate-600 mb-1">Lookup language</label>
              <select id="wdlookuplanguage" class="block w-full rounded-lg border-slate-300 text-xs shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 bg-white py-1.5 px-2.5 border"></select>
            </div>
            
            <div class="space-y-2 pt-1">
              <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700">
                <input name="returnConstraint" type="checkbox" id="returnSameAsLookup" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>Prioritize results in lookup language</span>
              </label>

              <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700">
                <input name="lookupConstraint" type="checkbox" id="strictLookup" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>Use language fallback</span>
              </label>
            </div>
          </div>

          <!-- Fuzzy matching settings -->
          <div class="mt-5 pt-4 border-t border-slate-100">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
              <i class="fas fa-spell-check text-slate-400"></i>
              <span>Fuzzy matching</span>
            </h4>
            <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 mb-2">
              <input type="checkbox" name="allow_ld" id="use_ld" onclick="open_ld_maxhits()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
              <span>Enable Levenshtein distance matching</span>
            </label>
            <div id="max_ld_tip" class="text-[11px] text-slate-400 hidden mb-1.5">Limits response to the x-nearest Levenshtein matches.</div>
            <input class="hidden w-full rounded-lg border-slate-300 text-xs shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-2.5 border" type="number" min="1" name="max_ld_hits" id="max_ld" value="5">
          </div>

          <!-- LATTE connector extraction block -->
          <div class="mt-5 pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fas fa-robot text-slate-400"></i>
                <span>LATTE connector</span>
              </h4>
              <?php if ($unlinkedCount > 0): ?>
                <span id="autoAnnoCountBadge" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                  <?php echo $unlinkedCount; ?> suggestions
                </span>
              <?php endif; ?>
            </div>

            <?php if (LATTECONNECTOR): ?>
            <div class="hidden space-y-2 mb-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-xs" id="connectorExpand">
              <div class="language space-y-1">
                <p><span class="font-semibold text-slate-600">Language ISO:</span> <span class="italic text-slate-800" id="detectedLanguageCode"></span></p>
                <p><span class="font-semibold text-slate-600">Language:</span> <span class="italic text-slate-800" id="detectedLanguage"></span></p>
                <p><span class="font-semibold text-slate-600">Certainty:</span> <span class="italic text-slate-800" id="detectedLanguageCertainty"></span></p>
              </div>
              <div class="entities space-y-1 pt-1.5 border-t border-slate-200">
                <p><span class="font-semibold text-slate-600">Extracted entities:</span> <span class="italic text-slate-800" id="amountOfEntities"></span></p>
                <p><span class="font-semibold text-slate-600">Model used:</span> <span class="italic text-slate-800" id="usedEntityModel"></span></p>
              </div>
            </div>
            <button id="extractorTrigger" onclick="entity_extraction_launcher()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-xs transition">
              <i class="fas fa-bolt text-[11px]"></i>
              <span>Extract named entities</span>
            </button>
            <div id="extractorProgress" class="hidden text-center text-xs text-slate-500 py-2"></div>
            <?php endif; ?>

            <?php if ($user->hasEditRights($user->myRole) >= 1): ?>
            <div class="mt-2.5">
              <button id="clearAutoAnnotationsBtn"
                      onclick="clearAutomaticAnnotations()"
                      class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition shadow-2xs <?php echo ($unlinkedCount === 0) ? 'hidden' : ''; ?>"
                      title="Remove all automatic suggestions from this text">
                <i class="fas fa-eraser text-[11px]"></i>
                <span>Clear automatic annotations</span>
              </button>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Entity container for extraction reports -->
        <div class="entities">
          <div class="report space-y-3" id="entitycontainer"></div>
        </div>
      </div>
    </div>

    <!-- Modern Slideover Inspector Drawer -->
    <div id="slideover-container" class="fixed inset-0 z-50 overflow-hidden invisible" aria-labelledby="slideover-title" role="dialog" aria-modal="true">
      <div id="slideover-bg" onclick="toggleSlide(0); ignoreSuggestion();" class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity duration-300"></div>

      <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
        <div id="slideover" class="w-full sm:w-[36rem] max-w-full bg-white shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out">
          <!-- Drawer header -->
          <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm shadow-2xs">
                <i class="fas fa-info-circle"></i>
              </div>
              <div>
                <h2 id="slideover-title" class="text-sm font-bold text-slate-900">Entity & annotation inspector</h2>
                <p class="text-[11px] text-slate-500">Inspect graph connections, authority records, and variants.</p>
              </div>
            </div>
            <button type="button" onclick="toggleSlide(0); ignoreSuggestion();" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
              <span class="sr-only">Close panel</span>
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Drawer dynamic content target -->
          <div id="slideoverDynamicContent" class="flex-1 overflow-y-auto p-6 space-y-6 text-slate-700 w-full"></div>
        </div>
      </div>
    </div>
  </main>

  <!-- Toast notification container -->
  <div id="toastContainer" class="fixed top-24 left-8 z-[99999] flex flex-col items-start gap-2 pointer-events-none" style="top: 5.5rem; left: 2rem; right: auto; width: auto; max-width: min(32rem, calc(100vw - 4rem)); z-index: 99999;"></div>

  <script>
    // Keyboard navigation shortcuts for browsing texts
    document.addEventListener('keydown', function(e) {
      if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
      if (e.key === 'ArrowLeft') {
        const prev = document.getElementById('prevTextBtn');
        if (prev && !prev.classList.contains('pointer-events-none')) prev.click();
      } else if (e.key === 'ArrowRight') {
        const next = document.getElementById('nextTextBtn');
        if (next && !next.classList.contains('pointer-events-none')) next.click();
      } else if (e.key === 'Escape') {
        toggleSlide(0);
        ignoreSuggestion();
      }
    });
  </script>

  <?php
  echo "<script>
    window.storedAnnotations = " . json_encode($existingAnnotation) . ";
    window.automatic_annotations = " . json_encode($unlinkedAnnotations) . ";
    window.rejected_annotations = " . json_encode($rejectedAnnotations) . ";
    var storedAnnotations = window.storedAnnotations;
    var automatic_annotations = window.automatic_annotations;
    var rejected_annotations = window.rejected_annotations;
  </script>";
  $linkedRelCount = isset($existingAnnotation['relations']) ? count($existingAnnotation['relations']) : 0;
  $unlinkedRelCount = is_array($unlinkedAnnotations) ? count($unlinkedAnnotations) : 0;
  if ($linkedRelCount > 0 || $unlinkedRelCount > 0) {
    echo "<script>visualizeStoredAnnotations();</script>";
  }
  ?>
  <script>
    helper_setWDLanguages(document.getElementById('wdlookuplanguage'));

    function getTextCsrfToken() {
      const m = document.querySelector('meta[name="csrf-token"]');
      return m ? m.getAttribute('content') : '';
    }

    function toggleTextCollectionPopover(forceState) {
      const pop = document.getElementById('textCollectionPopover');
      if (!pop) return;
      if (typeof forceState === 'boolean') {
        pop.classList.toggle('hidden', !forceState);
      } else {
        pop.classList.toggle('hidden');
      }
    }

    function refreshTextCollectionBadges() {
      const textNeoId = <?php echo (int)$neoId; ?>;
      fetch('/AJAX/collections.php?action=for_text&text_neoid=' + encodeURIComponent(textNeoId))
        .then(r => r.json())
        .then(data => {
          if (!data.success || !Array.isArray(data.collections)) return;
          const badgesEl = document.getElementById('textCollectionBadges');
          if (!badgesEl) return;
          badgesEl.innerHTML = '';
          const members = data.collections.filter(c => c.is_member);
          if (members.length === 0) {
            badgesEl.innerHTML = '<span class="text-xs text-slate-400 italic">None</span>';
            return;
          }
          members.forEach(c => {
            const isProj = c.scope === 'project';
            const a = document.createElement('a');
            a.href = '/collection/' + encodeURIComponent(c.collection_id);
            a.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors ' +
              (isProj ? 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100');
            const icon = document.createElement('i');
            icon.className = 'fas ' + (isProj ? 'fa-layer-group' : 'fa-bookmark') + ' text-[10px]';
            const lbl = document.createElement('span');
            lbl.textContent = c.name;
            a.appendChild(icon);
            a.appendChild(lbl);
            badgesEl.appendChild(a);
          });
        });
    }

    function toggleTextCollectionMembership(collectionId, isMember) {
      const textNeoId = <?php echo (int)$neoId; ?>;
      fetch('/AJAX/collections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getTextCsrfToken() },
        body: JSON.stringify({
          action: 'toggle_text',
          id: collectionId,
          text_neoid: textNeoId,
          member: isMember,
          csrf_token: getTextCsrfToken()
        })
      })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            refreshTextCollectionBadges();
            if (typeof showToast === 'function') {
              showToast(isMember ? 'Added text to collection.' : 'Removed text from collection.', 'success');
            }
          } else if (typeof showToast === 'function') {
            showToast(data.error || 'Could not update collection membership.', 'error');
          }
        });
    }

    function createQuickPersonalCollection(e) {
      e.preventDefault();
      const input = document.getElementById('quickCollectionName');
      const name = input ? input.value.trim() : '';
      if (!name) return;
      const textNeoId = <?php echo (int)$neoId; ?>;
      fetch('/AJAX/collections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getTextCsrfToken() },
        body: JSON.stringify({
          action: 'create',
          name: name,
          description: '',
          scope: 'personal',
          csrf_token: getTextCsrfToken()
        })
      })
        .then(r => r.json())
        .then(data => {
          if (data.success && data.collection) {
            return fetch('/AJAX/collections.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getTextCsrfToken() },
              body: JSON.stringify({
                action: 'toggle_text',
                id: data.collection.collection_id,
                text_neoid: textNeoId,
                member: true,
                csrf_token: getTextCsrfToken()
              })
            }).then(() => {
              window.location.reload();
            });
          } else if (typeof showToast === 'function') {
            showToast(data.error || 'Could not create collection.', 'error');
          }
        });
    }
  </script>
  </body>
</html>
