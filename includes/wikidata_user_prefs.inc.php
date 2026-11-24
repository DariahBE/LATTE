<?php
/**
 * Wikidata user preferences and caching manager.
 * Supports per-entity-type Wikidata P-tag selection, custom property lookup,
 * temporal data strategies (e.g. census evolution), and missing data indicators.
 */

if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__) . '/config/config.inc.php');
}
include_once(ROOT_DIR . '/includes/user.inc.php');

class Wikidata_user extends User {

    /**
     * Supported Wikipedia language link portals.
     */
    protected $wikidataPrefixToWikipediaLinks = array(
        'enwiki' => array('https://en.wikipedia.org/wiki/', 'English', true),
        'enwikivoyage' => array('https://en.wikivoyage.org/wiki/', 'Wikivoyage (EN)', true),
        'frwiki' => array('https://fr.wikipedia.org/wiki/', 'French', true),
        'nlwiki' => array('https://nl.wikipedia.org/wiki/', 'Dutch', false),
        'dewiki' => array('https://de.wikipedia.org/wiki/', 'German', false),
        'plwiki' => array('https://pl.wikipedia.org/wiki/', 'Polish', false),
        'ptwiki' => array('https://pt.wikipedia.org/wiki/', 'Portuguese', false),
        'eswiki' => array('https://es.wikipedia.org/wiki/', 'Spanish', false),
        'itwiki' => array('https://it.wikipedia.org/wiki/', 'Italian', false),
        'lawiki' => array('https://la.wikipedia.org/wiki/', 'Latin', false),
        'elwiki' => array('https://el.wikipedia.org/wiki/', 'Greek', false),
        'huwiki' => array('https://hu.wikipedia.org/wiki/', 'Hungarian', false),
        'ukwiki' => array('https://uk.wikipedia.org/wiki/', 'Ukrainian', false),
        'ruwiki' => array('https://ru.wikipedia.org/wiki/', 'Russian', false),
        'jawiki' => array('https://ja.wikipedia.org/wiki/', 'Japanese', false),
        'zhwiki' => array('https://zh.wikipedia.org/wiki/', 'Chinese', false),
        'arwiki' => array('https://ar.wikipedia.org/wiki/', 'Arabic', false),
        'svwiki' => array('https://sv.wikipedia.org/wiki/', 'Swedish', false),
        'cswiki' => array('https://cs.wikipedia.org/wiki/', 'Czech', false),
        'dawiki' => array('https://da.wikipedia.org/wiki/', 'Danish', false),
        'fiwiki' => array('https://fi.wikipedia.org/wiki/', 'Finnish', false),
        'nowiki' => array('https://no.wikipedia.org/wiki/', 'Norwegian', false),
        'trwiki' => array('https://tr.wikipedia.org/wiki/', 'Turkish', false),
        'hewiki' => array('https://he.wikipedia.org/wiki/', 'Hebrew', false),
    );

    /**
     * Supported language codes for Wikidata labels.
     */
    protected $wikidataPreferedLanguages = array(
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'pt' => 'Português',
        'es' => 'Español',
        'it' => 'Italiano',
        'hu' => 'Magyar',
        'uk' => 'Українська',
    );

    /**
     * Active evaluated preferences structure.
     */
    public $customPreferences = array(
        'shownPropertiesByEntity' => array(),
        'shownProperties' => array(),
        'fallbackLanguage' => 'en',
        'preferredLanguage' => array('en'),
        'showWikipediaLinksTo' => array('enwiki', 'frwiki'),
        'stringmatchWikipediaTitles' => array('enwiki', 'frwiki'),
        'prioritizeDetectedLanguage' => true,
    );

    /**
     * Ensure optional title preference columns exist in SQLite userdata.
     */
    public function ensureTitlePrefsColumn(): void {
        try {
            $this->sqlite->exec("ALTER TABLE userdata ADD COLUMN wd_prioritize_detected_language INTEGER DEFAULT 1");
        } catch (\Throwable $e) {
            // Column already exists or table locked.
        }
    }

    /**
     * Default catalog of recommended Wikidata P-tags organized by entity type.
     */
    public static function getDefaultPropertyCatalog(): array {
        return array(
            'Person' => array(
                'P21' => array('label' => 'Gender', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P569' => array('label' => 'Date of birth', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P570' => array('label' => 'Date of death', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P106' => array('label' => 'Occupation', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P19' => array('label' => 'Place of birth', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P20' => array('label' => 'Place of death', 'type' => 'str', 'enabled' => false, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P27' => array('label' => 'Country of citizenship', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P214' => array('label' => 'VIAF ID', 'type' => 'uri', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P244' => array('label' => 'Library of Congress ID', 'type' => 'uri', 'enabled' => false, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P18' => array('label' => 'Image', 'type' => 'img', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
            ),
            'Place' => array(
                'P625' => array('label' => 'Coordinates', 'type' => 'geo', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P17' => array('label' => 'Country', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P131' => array('label' => 'Located in administrative territory', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P1082' => array('label' => 'Population', 'type' => 'str', 'enabled' => true, 'show_missing' => true, 'temporal_mode' => 'history'),
                'P2044' => array('label' => 'Elevation above sea level', 'type' => 'str', 'enabled' => false, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P1566' => array('label' => 'GeoNames ID', 'type' => 'uri', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P2131' => array('label' => 'Nominal GDP', 'type' => 'str', 'enabled' => false, 'show_missing' => false, 'temporal_mode' => 'history'),
                'P18' => array('label' => 'Image', 'type' => 'img', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
            ),
            'Organization' => array(
                'P112' => array('label' => 'Founded by', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P571' => array('label' => 'Inception', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P159' => array('label' => 'Headquarters location', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P856' => array('label' => 'Official website', 'type' => 'uri', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P17' => array('label' => 'Country', 'type' => 'str', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P18' => array('label' => 'Image', 'type' => 'img', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
            ),
            '_global' => array(
                'P18' => array('label' => 'Image', 'type' => 'img', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P625' => array('label' => 'Coordinates', 'type' => 'geo', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
                'P214' => array('label' => 'VIAF ID', 'type' => 'uri', 'enabled' => true, 'show_missing' => false, 'temporal_mode' => 'latest'),
            ),
        );
    }

    /**
     * Get path to the compiled zero-query cache file for a specific user.
     */
    public static function getCacheFilePath(int $userId): string {
        return ROOT_DIR . '/storage/cache/wd_prefs_user_' . $userId . '.cache.php';
    }

    /**
     * Invalidate compiled preferences cache for a user.
     */
    public static function invalidateCache(int $userId): void {
        $cacheFile = self::getCacheFilePath($userId);
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    /**
     * Get entity types from the active model that have a Wikidata property.
     */
    public function labelIndicator(): array {
        $indicator = array();
        if (defined('NODEMODEL') && is_array(NODEMODEL)) {
            foreach (NODEMODEL as $key => $properties) {
                if (!is_array($properties)) {
                    continue;
                }
                foreach ($properties as $property => $propsettings) {
                    if (isset($propsettings[1]) && $propsettings[1] === 'wikidata') {
                        $indicator[$key] = $property;
                        break;
                    }
                }
            }
        }
        return $indicator;
    }

    /**
     * Build and return raw per-entity property settings for a user.
     */
    public function getUserPreferences(?int $userId = null): array {
        if ($userId === null) {
            $userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
        }

        if ($userId <= 0) {
            return self::getDefaultPropertyCatalog();
        }

        $cacheFile = self::getCacheFilePath($userId);
        if (file_exists($cacheFile)) {
            $cached = @include($cacheFile);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }

        $catalog = self::getDefaultPropertyCatalog();
        $activeEntities = $this->labelIndicator();
        foreach (array_keys($activeEntities) as $et) {
            if (!isset($catalog[$et])) {
                $catalog[$et] = $catalog['_global'];
            }
        }

        $stmt = $this->sqlite->prepare("SELECT wd_property_preferences FROM userdata WHERE id = ? LIMIT 1");
        $stmt->execute(array($userId));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $savedRaw = $row['wd_property_preferences'] ?? '';
        $saved = null;
        if (!empty($savedRaw)) {
            $decoded = json_decode($savedRaw, true);
            if (is_array($decoded)) {
                $saved = $decoded;
            }
        }

        if ($saved === null) {
            $saved = $catalog;
            $this->saveUserPreferences($userId, $saved);
        } else {
            // Ensure any newly added entity types are present.
            foreach (array_keys($activeEntities) as $et) {
                if (!isset($saved[$et])) {
                    $saved[$et] = $catalog[$et] ?? $catalog['_global'];
                }
            }
            $this->compileCache($userId, $saved);
        }

        return $saved;
    }

    /**
     * Save per-entity property settings into SQLite and update cache.
     */
    public function saveUserPreferences(int $userId, array $preferences): bool {
        if ($userId <= 0) {
            return false;
        }

        $jsonString = json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $stmt = $this->sqlite->prepare("UPDATE userdata SET wd_property_preferences = ? WHERE id = ?");
        $stmt->execute(array($jsonString, $userId));

        $this->compileCache($userId, $preferences);
        return true;
    }

    /**
     * Write optimized PHP cache for instant zero-query loading.
     */
    protected function compileCache(int $userId, array $preferences): void {
        $cacheFile = self::getCacheFilePath($userId);
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $content = "<?php\n// Compiled user Wikidata preferences cache.\nreturn " . var_export($preferences, true) . ";\n";
        @file_put_contents($cacheFile, $content, LOCK_EX);
    }

    /**
     * Build preferences into memory and cookies.
     */
    public function buildPreferences(): void {
        $userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
        $userEntityPrefs = $this->getUserPreferences($userId);

        $shownByEntity = array();
        $flatShown = array();

        foreach ($userEntityPrefs as $entityType => $properties) {
            $shownByEntity[$entityType] = array();
            if (!is_array($properties)) {
                continue;
            }
            foreach ($properties as $propCode => $propData) {
                $enabled = !empty($propData['enabled']);
                $label = $propData['label'] ?? $propCode;
                $type = $propData['type'] ?? 'str';
                $showMissing = !empty($propData['show_missing']);
                $temporalMode = $propData['temporal_mode'] ?? 'latest';

                $tuple = array($label, $enabled, $type, $showMissing, $temporalMode);
                $shownByEntity[$entityType][$propCode] = $tuple;

                if ($enabled) {
                    $flatShown[$propCode] = $tuple;
                }
            }
        }

        $this->customPreferences['shownPropertiesByEntity'] = $shownByEntity;
        $this->customPreferences['shownProperties'] = $flatShown;

        // Load language and link settings from SQLite userdata.
        if ($userId > 0) {
            $this->ensureTitlePrefsColumn();
            $stmt = $this->sqlite->prepare("SELECT wd_language_preferences, wd_wikilink_preferences, wd_titlestring_preferences, wd_prioritize_detected_language FROM userdata WHERE id = ? LIMIT 1");
            $stmt->execute(array($userId));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if (!empty($row['wd_language_preferences'])) {
                    $this->customPreferences['preferredLanguage'] = explode(',', $row['wd_language_preferences']);
                }
                if (!empty($row['wd_wikilink_preferences'])) {
                    $this->customPreferences['showWikipediaLinksTo'] = explode(',', $row['wd_wikilink_preferences']);
                }
                if (isset($row['wd_titlestring_preferences'])) {
                    $rawTitles = trim($row['wd_titlestring_preferences']);
                    if ($rawTitles === '') {
                        $this->customPreferences['stringmatchWikipediaTitles'] = array();
                    } else {
                        $this->customPreferences['stringmatchWikipediaTitles'] = array_values(array_filter(array_map('trim', explode(',', $rawTitles))));
                    }
                }
                if (isset($row['wd_prioritize_detected_language']) && $row['wd_prioritize_detected_language'] !== null) {
                    $this->customPreferences['prioritizeDetectedLanguage'] = (bool)$row['wd_prioritize_detected_language'];
                }
            }
        }
    }

    /**
     * Save title string matching preferences (prioritization and fallback languages).
     */
    public function saveTitlePreferences(int $userId, bool $prioritizeDetected, array $languages): bool {
        if ($userId <= 0) {
            return false;
        }

        $this->ensureTitlePrefsColumn();

        $cleaned = array();
        foreach ($languages as $l) {
            $code = strtolower(trim((string)$l));
            if ($code === '') continue;
            if (!str_ends_with($code, 'wiki')) {
                $code .= 'wiki';
            }
            if (preg_match('/^[a-z0-9_-]+wiki$/', $code) && !in_array($code, $cleaned, true)) {
                $cleaned[] = $code;
            }
        }

        $langString = implode(',', $cleaned);
        $prioritizeVal = $prioritizeDetected ? 1 : 0;

        $stmt = $this->sqlite->prepare("UPDATE userdata SET wd_titlestring_preferences = ?, wd_prioritize_detected_language = ? WHERE id = ?");
        $stmt->execute(array($langString, $prioritizeVal, $userId));

        $this->customPreferences['stringmatchWikipediaTitles'] = $cleaned;
        $this->customPreferences['prioritizeDetectedLanguage'] = $prioritizeDetected;

        self::invalidateCache($userId);
        return true;
    }

    /**
     * Store profile settings from standard POST submissions.
     */
    public function storeProfileSettings(string $formname, array $keys): bool {
        $userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
        if ($userId <= 0) {
            return false;
        }

        if ($formname === 'links') {
            $valid = array_intersect($keys, array_keys($this->wikidataPrefixToWikipediaLinks));
            $stmt = $this->sqlite->prepare("UPDATE userdata SET wd_wikilink_preferences = ? WHERE id = ?");
            $stmt->execute(array(implode(',', $valid), $userId));
            self::invalidateCache($userId);
            return true;
        }

        if ($formname === 'titles') {
            $prioritize = isset($_POST['prioritize_detected_language']);
            $rawLangs = $_POST['fallback_languages'] ?? $keys;
            if (!is_array($rawLangs)) {
                $rawLangs = explode(',', (string)$rawLangs);
            }
            return $this->saveTitlePreferences($userId, $prioritize, $rawLangs);
        }

        if ($formname === 'language') {
            $valid = array_intersect($keys, array_keys($this->wikidataPreferedLanguages));
            $stmt = $this->sqlite->prepare("UPDATE userdata SET wd_language_preferences = ? WHERE id = ?");
            $stmt->execute(array(implode(',', $valid), $userId));
            self::invalidateCache($userId);
            return true;
        }

        return false;
    }

    /**
     * Render settings dictionary passed to JavaScript client.
     */
    public function makeSettingsDictionary(?string $entityType = null): array {
        if (empty($this->customPreferences['shownPropertiesByEntity'])) {
            $this->buildPreferences();
        }

        $linksDict = array();
        foreach ($this->customPreferences['showWikipediaLinksTo'] as $prefix) {
            if (isset($this->wikidataPrefixToWikipediaLinks[$prefix])) {
                $linksDict[$prefix] = $this->wikidataPrefixToWikipediaLinks[$prefix];
            }
        }

        $preferredLang = !empty($this->customPreferences['preferredLanguage'])
            ? $this->customPreferences['preferredLanguage'][0]
            : 'en';

        $langDict = array($preferredLang => ($this->wikidataPreferedLanguages[$preferredLang] ?? 'English'));

        $titlesDict = array();
        foreach ($this->customPreferences['stringmatchWikipediaTitles'] as $prefix) {
            $cleanPrefix = strtolower(trim($prefix));
            if ($cleanPrefix === '') continue;
            if (isset($this->wikidataPrefixToWikipediaLinks[$cleanPrefix])) {
                $titlesDict[$cleanPrefix] = $this->wikidataPrefixToWikipediaLinks[$cleanPrefix];
            } else {
                $langCode = preg_replace('/wiki$/', '', $cleanPrefix);
                $titlesDict[$cleanPrefix] = array(
                    "https://{$langCode}.wikipedia.org/wiki/",
                    strtoupper($langCode) . ' (' . $cleanPrefix . ')'
                );
            }
        }

        $flatShown = $this->customPreferences['shownProperties'];
        if ($entityType !== null && isset($this->customPreferences['shownPropertiesByEntity'][$entityType])) {
            $flatShown = array();
            foreach ($this->customPreferences['shownPropertiesByEntity'][$entityType] as $code => $tuple) {
                if (!empty($tuple[1])) {
                    $flatShown[$code] = $tuple;
                }
            }
        }

        return array(
            'shownPropertiesByEntity' => $this->customPreferences['shownPropertiesByEntity'],
            'shownProperties' => $flatShown,
            'showWikipediaLinksTo' => $linksDict,
            'preferredLanguage' => $langDict,
            'fallbackLanguage' => $this->customPreferences['fallbackLanguage'],
            'stringmatchWikipediaTitles' => $titlesDict,
            'prioritizeDetectedLanguage' => !empty($this->customPreferences['prioritizeDetectedLanguage']),
        );
    }

    /**
     * Legacy HTML generator for links and titles tabs.
     */
    public function generateForm(string $formname): string {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $actionUrl = $baseUrl . '/user/profileUpdate.php';

        include_once(ROOT_DIR . '/includes/csrf.inc.php');
        $csrfManager = new CsrfTokenManager();
        $csrfVal = $csrfManager->getToken();

        $data = $this->wikidataPrefixToWikipediaLinks;
        $selected = ($formname === 'titles')
            ? $this->customPreferences['stringmatchWikipediaTitles']
            : $this->customPreferences['showWikipediaLinksTo'];

        $salt = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8);
        $output = '<form method="POST" action="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '" class="space-y-4">';
        $output .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfVal, ENT_QUOTES, 'UTF-8') . '">';
        $output .= '<input type="hidden" name="form_type_setting_application_value" value="' . htmlspecialchars($formname, ENT_QUOTES, 'UTF-8') . '">';
        $output .= '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">';

        foreach ($data as $key => $value) {
            $checked = in_array($key, $selected, true) ? ' checked ' : '';
            $saltedKey = $salt . $key;
            $output .= '<label for="' . $saltedKey . '" class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200/80 bg-white hover:bg-slate-50/80 cursor-pointer transition-all shadow-2xs">
                <input type="checkbox" id="' . $saltedKey . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" ' . $checked . ' class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                <span class="text-xs font-medium text-slate-700 leading-snug">' . htmlspecialchars($value[1], ENT_QUOTES, 'UTF-8') . '</span>
            </label>';
        }

        $output .= '</div>';
        $output .= '<div class="pt-2 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-xs transition-colors">
                <i class="fas fa-save text-[11px]"></i>
                <span>Save ' . htmlspecialchars($formname, ENT_QUOTES, 'UTF-8') . ' preferences</span>
            </button>
        </div></form>';

        return $output;
    }
}
