<?php
/**
 * Entity deduplication and merge engine.
 * Supports same-type and cross-type merging in a single Neo4j transaction,
 * and records :MergedEntity redirects so historical /URI/ and /API/ links resolve to the canonical entity.
 */
class EntityMerger {
    protected $client;

    public function __construct($client) {
        $this->client = $client;
    }

    /**
     * Check whether the user is allowed to merge entities (projectlead or admin).
     */
    public function canMerge(User $user): bool {
        if (!$user->myId) {
            return false;
        }
        return $user->isAdmin() || $user->hasEditRights($user->myRole) >= 3;
    }

    /**
     * Return all mergeable entity labels defined in CORENODES.
     */
    public function getAllowedEntityTypes(): array {
        $excluded = [
            defined('TEXNODE') ? TEXNODE : 'Text',
            defined('ANNONODE') ? ANNONODE : 'Annotation',
            'Variant',
            'See_Also',
            'Collection',
        ];
        return array_values(array_diff(array_keys(CORENODES), $excluded));
    }

    /**
     * Extract a node's display title from its properties.
     */
    protected function extractDisplayName(string $label, array $props, int $neoId): string {
        $model = NODEMODEL[$label] ?? [];
        foreach (['name', 'named', 'label', 'title', 'qd'] as $preferred) {
            if (!empty($props[$preferred])) {
                return (string)$props[$preferred];
            }
        }
        foreach ($model as $key => $meta) {
            if (($meta[1] ?? '') === 'string' && !empty($props[$key])) {
                return (string)$props[$key];
            }
        }
        return $label . ' #' . $neoId;
    }

    /**
     * Extract the canonical /URI/ identifier for a node (matching stable.php / generateURI logic).
     */
    public function getCanonicalIdentifier(string $label, array $props): string {
        $pk = 'uid';
        if (defined('PRIMARIES') && !empty(PRIMARIES[$label])) {
            $pk = PRIMARIES[$label];
        }
        if (!empty($props[$pk])) {
            return (string)$props[$pk];
        }
        if (!empty($props['uid'])) {
            return (string)$props['uid'];
        }
        return '';
    }

    /**
     * Find candidate duplicate entity pairs by shared Wikidata Q-ID or shared normalized Variant spelling.
     */
    public function findDuplicateCandidates(?string $entityType = null, int $limit = 50): array {
        $allowed = $this->getAllowedEntityTypes();
        if (empty($allowed)) {
            return [];
        }
        $typeFilter = ($entityType && in_array($entityType, $allowed, true)) ? [$entityType] : $allowed;

        $candidates = [];
        $seenPairs = [];

        // 1. Entities sharing the same Wikidata Q-ID (including cross-type).
        $wdProps = ['wikidata_id'];
        foreach ($allowed as $lbl) {
            foreach (NODEMODEL[$lbl] ?? [] as $k => $meta) {
                if (strtolower($meta[1] ?? '') === 'wikidata' && !in_array($k, $wdProps, true)) {
                    $wdProps[] = $k;
                }
            }
        }

        $wdQuery = 'MATCH (a), (b)
                    WHERE id(a) < id(b)
                      AND any(la IN labels(a) WHERE la IN $typeFilter)
                      AND any(lb IN labels(b) WHERE lb IN $allowed)
                      AND any(ka IN $wdProps WHERE a[ka] IS NOT NULL AND a[ka] <> "" AND any(kb IN $wdProps WHERE b[kb] = a[ka]))
                    RETURN a, b
                    LIMIT $lim';
        $wdRes = $this->client->run($wdQuery, [
            'typeFilter' => $typeFilter,
            'allowed' => $allowed,
            'wdProps' => $wdProps,
            'lim' => $limit,
        ]);

        foreach ($wdRes as $row) {
            $a = $row['a'];
            $b = $row['b'];
            $aId = (int)$a['id'];
            $bId = (int)$b['id'];
            $pairKey = $aId . '_' . $bId;
            if (isset($seenPairs[$pairKey])) {
                continue;
            }
            $seenPairs[$pairKey] = true;
            $aLabel = (string)($a['labels'][0] ?? 'Entity');
            $bLabel = (string)($b['labels'][0] ?? 'Entity');
            $aProps = $a['properties']->toArray();
            $bProps = $b['properties']->toArray();
            $candidates[] = [
                'reason' => 'Shared Wikidata Q-ID',
                'source' => [
                    'neo_id' => $aId,
                    'label' => $aLabel,
                    'display' => $this->extractDisplayName($aLabel, $aProps, $aId),
                    'uid' => (string)($aProps['uid'] ?? ''),
                ],
                'target' => [
                    'neo_id' => $bId,
                    'label' => $bLabel,
                    'display' => $this->extractDisplayName($bLabel, $bProps, $bId),
                    'uid' => (string)($bProps['uid'] ?? ''),
                ],
            ];
        }

        // 2. Entities sharing a normalized Variant spelling (same or cross-type).
        $varQuery = 'MATCH (v1:Variant)-[:same_as]->(a), (v2:Variant)-[:same_as]->(b)
                     WHERE id(a) < id(b)
                       AND any(la IN labels(a) WHERE la IN $typeFilter)
                       AND any(lb IN labels(b) WHERE lb IN $allowed)
                       AND v1.variant IS NOT NULL AND v2.variant IS NOT NULL
                       AND toLower(trim(v1.variant)) = toLower(trim(v2.variant))
                     RETURN DISTINCT a, b, v1.variant AS shared_variant
                     LIMIT $lim';
        $varRes = $this->client->run($varQuery, [
            'typeFilter' => $typeFilter,
            'allowed' => $allowed,
            'lim' => $limit,
        ]);

        foreach ($varRes as $row) {
            $a = $row['a'];
            $b = $row['b'];
            $aId = (int)$a['id'];
            $bId = (int)$b['id'];
            $pairKey = $aId . '_' . $bId;
            if (isset($seenPairs[$pairKey])) {
                continue;
            }
            $seenPairs[$pairKey] = true;
            $aLabel = (string)($a['labels'][0] ?? 'Entity');
            $bLabel = (string)($b['labels'][0] ?? 'Entity');
            $aProps = $a['properties']->toArray();
            $bProps = $b['properties']->toArray();
            $candidates[] = [
                'reason' => 'Shared spelling: "' . (string)$row['shared_variant'] . '"',
                'source' => [
                    'neo_id' => $aId,
                    'label' => $aLabel,
                    'display' => $this->extractDisplayName($aLabel, $aProps, $aId),
                    'uid' => (string)($aProps['uid'] ?? ''),
                ],
                'target' => [
                    'neo_id' => $bId,
                    'label' => $bLabel,
                    'display' => $this->extractDisplayName($bLabel, $bProps, $bId),
                    'uid' => (string)($bProps['uid'] ?? ''),
                ],
            ];
        }

        return $candidates;
    }

    /**
     * Search entities by string property, variant, UID, or Neo4j ID for manual selection.
     */
    public function searchEntities(string $query, ?string $entityType = null, int $limit = 30): array {
        $allowed = $this->getAllowedEntityTypes();
        if (empty($allowed)) {
            return [];
        }
        $typeFilter = ($entityType && in_array($entityType, $allowed, true)) ? [$entityType] : $allowed;
        $qTrim = trim($query);

        $stringProps = ['uid'];
        foreach ($allowed as $lbl) {
            foreach (NODEMODEL[$lbl] ?? [] as $k => $meta) {
                if (in_array(strtolower($meta[1] ?? ''), ['string', 'wikidata', 'uri'], true) && !in_array($k, $stringProps, true)) {
                    $stringProps[] = $k;
                }
            }
        }
        $stringProps[] = 'wikidata_id';

        if ($qTrim === '') {
            $cypher = 'MATCH (n)
                       WHERE any(l IN labels(n) WHERE l IN $typeFilter)
                       OPTIONAL MATCH (a:Annotation)-[:references]->(n)
                       RETURN n, count(DISTINCT a) AS anno_count
                       ORDER BY anno_count DESC, id(n) DESC
                       LIMIT $lim';
            $res = $this->client->run($cypher, ['typeFilter' => $typeFilter, 'lim' => $limit]);
        } else {
            $cypher = 'MATCH (n)
                       WHERE any(l IN labels(n) WHERE l IN $typeFilter)
                         AND (
                           toString(id(n)) = $exactQ
                           OR any(k IN $stringProps WHERE n[k] IS NOT NULL AND toLower(toString(n[k])) CONTAINS $lowerQ)
                           OR EXISTS {
                             MATCH (v:Variant)-[:same_as]->(n)
                             WHERE v.variant IS NOT NULL AND toLower(v.variant) CONTAINS $lowerQ
                           }
                         )
                       OPTIONAL MATCH (a:Annotation)-[:references]->(n)
                       RETURN n, count(DISTINCT a) AS anno_count
                       ORDER BY anno_count DESC, id(n) DESC
                       LIMIT $lim';
            $res = $this->client->run($cypher, [
                'typeFilter' => $typeFilter,
                'stringProps' => $stringProps,
                'exactQ' => $qTrim,
                'lowerQ' => mb_strtolower($qTrim, 'UTF-8'),
                'lim' => $limit,
            ]);
        }

        $items = [];
        foreach ($res as $row) {
            $n = $row['n'];
            $nid = (int)$n['id'];
            $lbl = (string)($n['labels'][0] ?? 'Entity');
            $props = $n['properties']->toArray();
            $items[] = [
                'neo_id' => $nid,
                'label' => $lbl,
                'display' => $this->extractDisplayName($lbl, $props, $nid),
                'uid' => (string)($props['uid'] ?? ''),
                'anno_count' => (int)$row['anno_count'],
            ];
        }
        return $items;
    }

    /**
     * Fetch detailed node stats and property conflicts for a Source -> Target merge preview.
     */
    public function previewMerge(int $sourceNeoId, int $targetNeoId): array {
        if ($sourceNeoId <= 0 || $targetNeoId <= 0 || $sourceNeoId === $targetNeoId) {
            throw new \InvalidArgumentException('Please select two distinct valid entities.');
        }

        $allowed = $this->getAllowedEntityTypes();
        $fetchNode = function (int $nid) use ($allowed): array {
            $res = $this->client->run(
                'MATCH (n) WHERE id(n) = $nid
                 OPTIONAL MATCH (a:Annotation)-[:references]->(n)
                 OPTIONAL MATCH (v:Variant)-[:same_as]->(n)
                 OPTIONAL MATCH (n)-[:see_also]-(s:See_Also)
                 RETURN n,
                        count(DISTINCT a) AS anno_count,
                        collect(DISTINCT v.variant) AS variants,
                        count(DISTINCT s) AS kb_count',
                ['nid' => $nid]
            );
            if ($res->count() === 0) {
                throw new \RuntimeException("Entity #$nid was not found.");
            }
            $row = $res->first();
            $n = $row->get('n');
            $label = (string)($n['labels'][0] ?? '');
            if (!in_array($label, $allowed, true)) {
                throw new \RuntimeException("Node #$nid ($label) is not a mergeable entity type.");
            }
            $props = $n['properties']->toArray();
            $variants = array_values(array_filter(iterator_to_array($row->get('variants')), function ($v) {
                return $v !== null && $v !== '';
            }));

            return [
                'neo_id' => $nid,
                'label' => $label,
                'display' => $this->extractDisplayName($label, $props, $nid),
                'uid' => (string)($props['uid'] ?? ''),
                'canonical_id' => $this->getCanonicalIdentifier($label, $props),
                'properties' => $props,
                'anno_count' => (int)$row->get('anno_count'),
                'variants' => $variants,
                'kb_count' => (int)$row->get('kb_count'),
            ];
        };

        $source = $fetchNode($sourceNeoId);
        $target = $fetchNode($targetNeoId);

        // Build target schema property comparison (supports cross-type merges).
        $targetModel = NODEMODEL[$target['label']] ?? [];
        $propertyResolution = [];
        foreach ($targetModel as $propKey => $meta) {
            $sVal = $source['properties'][$propKey] ?? null;
            $tVal = $target['properties'][$propKey] ?? null;
            $sStr = ($sVal !== null) ? (string)$sVal : '';
            $tStr = ($tVal !== null) ? (string)$tVal : '';

            $hasConflict = ($sStr !== '' && $tStr !== '' && $sStr !== $tStr);
            $propertyResolution[] = [
                'key' => $propKey,
                'label' => $meta[0] ?? $propKey,
                'type' => $meta[1] ?? 'string',
                'source_value' => $sStr,
                'target_value' => $tStr,
                'default_choice' => ($tStr !== '') ? 'target' : (($sStr !== '') ? 'source' : 'target'),
                'has_conflict' => $hasConflict,
            ];
        }

        // Also check fallback wikidata_id if target has no wikidata-typed property in its schema.
        $targetHasWdSchema = false;
        foreach ($targetModel as $meta) {
            if (strtolower($meta[1] ?? '') === 'wikidata') {
                $targetHasWdSchema = true;
                break;
            }
        }
        if (!$targetHasWdSchema) {
            $sWd = (string)($source['properties']['wikidata_id'] ?? '');
            $tWd = (string)($target['properties']['wikidata_id'] ?? '');
            if ($sWd !== '' || $tWd !== '') {
                $propertyResolution[] = [
                    'key' => 'wikidata_id',
                    'label' => 'Wikidata ID',
                    'type' => 'wikidata',
                    'source_value' => $sWd,
                    'target_value' => $tWd,
                    'default_choice' => ($tWd !== '') ? 'target' : 'source',
                    'has_conflict' => ($sWd !== '' && $tWd !== '' && $sWd !== $tWd),
                ];
            }
        }

        // Also list properties set on Source that do NOT exist in Target's schema (e.g. cross-type merges).
        $targetKeysSet = array_fill_keys(array_column($propertyResolution, 'key'), true);
        $sourceOnlyProperties = [];
        $systemKeys = ['uid', 'priv_user'];
        foreach ($source['properties'] as $sKey => $sVal) {
            if (in_array($sKey, $systemKeys, true) || isset($targetKeysSet[$sKey])) {
                continue;
            }
            $sStr = ($sVal !== null) ? (string)$sVal : '';
            if ($sStr === '') {
                continue;
            }
            $sourceLabel = NODEMODEL[$source['label']][$sKey][0] ?? $sKey;
            $sourceOnlyProperties[] = [
                'key' => $sKey,
                'label' => $sourceLabel,
                'value' => $sStr,
            ];
        }

        return [
            'source' => $source,
            'target' => $target,
            'cross_type' => ($source['label'] !== $target['label']),
            'property_resolution' => $propertyResolution,
            'source_only_properties' => $sourceOnlyProperties,
        ];
    }

    /**
     * Atomically merge sourceNeoId into targetNeoId inside a Neo4j transaction,
     * transferring all relationships and creating a :MergedEntity redirect record.
     */
    public function executeMerge(int $sourceNeoId, int $targetNeoId, array $resolvedProperties, User $user): array {
        if (!$this->canMerge($user)) {
            throw new \RuntimeException('Insufficient permissions to merge entities.');
        }

        $preview = $this->previewMerge($sourceNeoId, $targetNeoId);
        $source = $preview['source'];
        $target = $preview['target'];

        $targetModel = NODEMODEL[$target['label']] ?? [];
        $allowedTargetKeys = array_keys($targetModel);
        $allowedTargetKeys[] = 'wikidata_id';

        $updates = [];
        foreach ($resolvedProperties as $k => $choiceOrSpec) {
            if (!in_array($k, $allowedTargetKeys, true)) {
                continue;
            }
            if (is_array($choiceOrSpec)) {
                $mode = $choiceOrSpec['mode'] ?? 'target';
                if ($mode === 'source') {
                    if (isset($source['properties'][$k]) && $source['properties'][$k] !== '') {
                        $updates[$k] = $source['properties'][$k];
                    }
                } elseif ($mode === 'custom') {
                    $customVal = trim((string)($choiceOrSpec['value'] ?? ''));
                    if ($customVal !== '') {
                        $updates[$k] = $customVal;
                    }
                } elseif ($mode === 'target') {
                    if (isset($target['properties'][$k]) && $target['properties'][$k] !== '') {
                        $updates[$k] = $target['properties'][$k];
                    }
                }
            } elseif ($choiceOrSpec === 'source') {
                if (isset($source['properties'][$k]) && $source['properties'][$k] !== '') {
                    $updates[$k] = $source['properties'][$k];
                }
            } elseif ($choiceOrSpec === 'target') {
                if (isset($target['properties'][$k]) && $target['properties'][$k] !== '') {
                    $updates[$k] = $target['properties'][$k];
                }
            } elseif (is_string($choiceOrSpec) && $choiceOrSpec !== '') {
                $updates[$k] = $choiceOrSpec;
            }
        }

        // Fill any empty target properties from source if not explicitly specified.
        foreach ($preview['property_resolution'] as $pr) {
            $k = $pr['key'];
            if (!array_key_exists($k, $updates) && $pr['target_value'] === '' && $pr['source_value'] !== '') {
                $updates[$k] = $source['properties'][$k];
            }
        }

        $tsx = $this->client->beginTransaction();
        try {
            // 1. Update resolved properties on Target.
            if (!empty($updates)) {
                foreach ($updates as $propKey => $propVal) {
                    $safeProp = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$propKey);
                    if ($safeProp === '' || $safeProp === 'uid') {
                        continue;
                    }
                    $tsx->run(
                        "MATCH (t) WHERE id(t) = \$tid SET t.`{$safeProp}` = \$val",
                        ['tid' => $targetNeoId, 'val' => $propVal]
                    );
                }
            }

            // 2. Re-point annotations: (a:Annotation)-[:references]->(source) -> (target).
            $tsx->run(
                'MATCH (a:Annotation)-[r:references]->(s) WHERE id(s) = $sid
                 MATCH (t) WHERE id(t) = $tid
                 MERGE (a)-[:references]->(t)
                 DELETE r',
                ['sid' => $sourceNeoId, 'tid' => $targetNeoId]
            );

            // 3. Re-point variants: (v:Variant)-[:same_as]->(source) -> (target).
            $tsx->run(
                'MATCH (v:Variant)-[r:same_as]->(s) WHERE id(s) = $sid
                 MATCH (t) WHERE id(t) = $tid
                 MERGE (v)-[:same_as]->(t)
                 DELETE r',
                ['sid' => $sourceNeoId, 'tid' => $targetNeoId]
            );

            // 4. Re-point knowledge base links: (source)-[:see_also]-(kb:See_Also) -> (target).
            $tsx->run(
                'MATCH (s)-[r:see_also]->(kb:See_Also) WHERE id(s) = $sid
                 MATCH (t) WHERE id(t) = $tid
                 MERGE (t)-[:see_also]->(kb)
                 DELETE r',
                ['sid' => $sourceNeoId, 'tid' => $targetNeoId]
            );
            $tsx->run(
                'MATCH (kb:See_Also)-[r:see_also]->(s) WHERE id(s) = $sid
                 MATCH (t) WHERE id(t) = $tid
                 MERGE (t)-[:see_also]->(kb)
                 DELETE r',
                ['sid' => $sourceNeoId, 'tid' => $targetNeoId]
            );

            // 5. Preserve creator audit trail.
            $tsx->run(
                'MATCH (u:priv_user)-[r:priv_created]->(s) WHERE id(s) = $sid
                 MATCH (t) WHERE id(t) = $tid
                 MERGE (u)-[:priv_created]->(t)
                 DELETE r',
                ['sid' => $sourceNeoId, 'tid' => $targetNeoId]
            );

            // 6. Collapse any existing :MergedEntity redirects that pointed to Source so they point directly to Target.
            $tsx->run(
                'MATCH (m:MergedEntity)-[r:MERGED_INTO]->(s) WHERE id(s) = $sid
                 MATCH (t) WHERE id(t) = $tid
                 MERGE (m)-[:MERGED_INTO]->(t)
                 DELETE r',
                ['sid' => $sourceNeoId, 'tid' => $targetNeoId]
            );

            // 7. Create :MergedEntity redirect record for Source -> Target.
            $tsx->run(
                'MATCH (t) WHERE id(t) = $tid
                 CREATE (m:MergedEntity {
                     old_type: $oldType,
                     old_pk: $oldPk,
                     old_uid: $oldUid,
                     merged_at: $mergedAt,
                     merged_by: $mergedBy
                 })-[:MERGED_INTO]->(t)',
                [
                    'tid' => $targetNeoId,
                    'oldType' => $source['label'],
                    'oldPk' => $source['canonical_id'],
                    'oldUid' => $source['uid'],
                    'mergedAt' => gmdate('c'),
                    'mergedBy' => (int)$user->myId,
                ]
            );

            // 8. Delete the duplicate Source node.
            $tsx->run(
                'MATCH (s) WHERE id(s) = $sid DETACH DELETE s',
                ['sid' => $sourceNeoId]
            );

            $tsx->commit();
        } catch (\Throwable $e) {
            $tsx->rollback();
            throw $e;
        }

        // Refresh target canonical identifier after property updates.
        $refreshed = $this->client->run('MATCH (t) WHERE id(t) = $tid RETURN t', ['tid' => $targetNeoId]);
        $tNode = $refreshed->first()->get('t');
        $tProps = $tNode['properties']->toArray();
        $targetCanonicalId = $this->getCanonicalIdentifier($target['label'], $tProps);

        return [
            'success' => true,
            'deleted_source_id' => $sourceNeoId,
            'deleted_source_label' => $source['label'],
            'target_id' => $targetNeoId,
            'target_label' => $target['label'],
            'target_canonical_id' => $targetCanonicalId,
            'transferred_annotations' => $source['anno_count'],
            'transferred_variants' => count($source['variants']),
            'transferred_kb_links' => $source['kb_count'],
        ];
    }

    /**
     * Resolve a historical /URI/<type>/<id> or /API/<type>/<id> to its merged target entity.
     */
    public function resolveMergedRedirect(string $oldType, string $oldIdentifier): ?array {
        $oldType = ucfirst(trim($oldType));
        $oldIdentifier = trim($oldIdentifier);
        if ($oldType === '' || $oldIdentifier === '') {
            return null;
        }

        $res = $this->client->run(
            'MATCH (m:MergedEntity)-[:MERGED_INTO]->(t)
             WHERE toLower(m.old_type) = toLower($oldType)
               AND (m.old_pk = $ident OR m.old_uid = $ident)
             RETURN t
             LIMIT 1',
            ['oldType' => $oldType, 'ident' => $oldIdentifier]
        );
        if ($res->count() === 0) {
            return null;
        }

        $tNode = $res->first()->get('t');
        $targetLabel = (string)($tNode['labels'][0] ?? '');
        if ($targetLabel === '') {
            return null;
        }
        $targetProps = $tNode['properties']->toArray();
        $targetId = $this->getCanonicalIdentifier($targetLabel, $targetProps);
        if ($targetId === '') {
            return null;
        }

        return [
            'target_type' => $targetLabel,
            'target_id' => $targetId,
        ];
    }
}
