<?php
/**
 * Collection manager for organizing texts into personal and project-level collections.
 * Collections are stored as system-managed :Collection nodes in Neo4j, separate from NODEMODEL.
 */
class CollectionManager {
    protected $client;

    public function __construct($client) {
        $this->client = $client;
    }

    /**
     * Generate a UUIDv4 identifier.
     */
    protected function generateUuidV4(): string {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Return the configured Text node label and primary key property.
     */
    protected function getTextConfig(): array {
        $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';
        $textPk = 'text_id';
        if (defined('PRIMARIES') && isset(PRIMARIES[$textLabel]) && PRIMARIES[$textLabel]) {
            $textPk = PRIMARIES[$textLabel];
        }
        $textProp = defined('TEXNODETEXT') ? TEXNODETEXT : 'text';
        return [$textLabel, $textPk, $textProp];
    }

    /**
     * Check whether a user can create, edit, or delete project-level collections.
     */
    public function canCreateProjectCollection(User $user): bool {
        if (!$user->myId) {
            return false;
        }
        return $user->isAdmin() || $user->hasEditRights($user->myRole) >= 3;
    }

    /**
     * Check whether a user can add or remove texts in existing project-level collections.
     */
    public function canManageProjectMembership(User $user): bool {
        if (!$user->myId) {
            return false;
        }
        return $user->isAdmin() || $user->hasEditRights($user->myRole) >= 2;
    }

    /**
     * Check whether a user can create personal collections.
     */
    public function canCreatePersonalCollection(User $user): bool {
        return boolval($user->myId) && boolval($user->neoId);
    }

    /**
     * Check whether a user can edit metadata (name/description) or delete a collection.
     */
    public function canEditCollectionMetadata(User $user, array $collection): bool {
        if (!$user->myId) {
            return false;
        }
        if (($collection['scope'] ?? '') === 'project') {
            return $this->canCreateProjectCollection($user);
        }
        return (int)($collection['owner_neo_id'] ?? 0) === (int)$user->neoId;
    }

    /**
     * Check whether a user can add or remove texts in a collection.
     */
    public function canEditCollectionMembership(User $user, array $collection): bool {
        if (!$user->myId) {
            return false;
        }
        if (($collection['scope'] ?? '') === 'project') {
            return $this->canManageProjectMembership($user);
        }
        return (int)($collection['owner_neo_id'] ?? 0) === (int)$user->neoId;
    }

    /**
     * Normalize a Neo4j Collection record into a plain associative array.
     */
    protected function formatCollectionRow($node, int $textCount = 0, ?int $ownerNeoId = null): array {
        $props = $node['properties'] ?? [];
        return [
            'neo_id' => (int)($node['id'] ?? 0),
            'collection_id' => (int)($props['collection_id'] ?? 0),
            'uid' => (string)($props['uid'] ?? ''),
            'name' => (string)($props['name'] ?? ''),
            'description' => (string)($props['description'] ?? ''),
            'scope' => (string)($props['scope'] ?? 'personal'),
            'created_at' => (string)($props['created_at'] ?? ''),
            'updated_at' => (string)($props['updated_at'] ?? ''),
            'text_count' => $textCount,
            'owner_neo_id' => $ownerNeoId,
        ];
    }

    /**
     * List all collections accessible to the current user, grouped by scope.
     */
    public function listAccessibleCollections(?int $userNeoId, bool $textsArePublic): array {
        [$textLabel] = $this->getTextConfig();
        $project = [];
        $personal = [];

        if ($textsArePublic || $userNeoId) {
            $projQuery = "MATCH (c:Collection {scope: 'project'})
                          OPTIONAL MATCH (t:$textLabel)-[:IN_COLLECTION]->(c)
                          RETURN c, count(DISTINCT t) AS text_count
                          ORDER BY c.collection_id ASC";
            $projRes = $this->client->run($projQuery);
            foreach ($projRes as $row) {
                $project[] = $this->formatCollectionRow($row['c'], (int)$row['text_count']);
            }
        }

        if ($userNeoId) {
            $persQuery = "MATCH (u:priv_user)-[:OWNS_COLLECTION]->(c:Collection {scope: 'personal'})
                          WHERE id(u) = \$userNeoId
                          OPTIONAL MATCH (t:$textLabel)-[:IN_COLLECTION]->(c)
                          RETURN c, count(DISTINCT t) AS text_count, id(u) AS owner_id
                          ORDER BY c.collection_id ASC";
            $persRes = $this->client->run($persQuery, ['userNeoId' => (int)$userNeoId]);
            foreach ($persRes as $row) {
                $personal[] = $this->formatCollectionRow($row['c'], (int)$row['text_count'], (int)$row['owner_id']);
            }
        }

        return [
            'project' => $project,
            'personal' => $personal,
        ];
    }

    /**
     * Fetch a single collection by stable collection_id or UUIDv4 uid, including its member texts.
     */
    public function getCollectionByIdentifier($identifier, ?int $userNeoId, bool $textsArePublic): ?array {
        $identifier = trim((string)$identifier);
        if ($identifier === '') {
            return null;
        }

        if (ctype_digit($identifier)) {
            $where = 'c.collection_id = $cid';
            $params = ['cid' => (int)$identifier];
        } else {
            $where = 'c.uid = $uid';
            $params = ['uid' => $identifier];
        }

        $query = "MATCH (c:Collection)
                  WHERE $where
                  OPTIONAL MATCH (u:priv_user)-[:OWNS_COLLECTION]->(c)
                  RETURN c, id(u) AS owner_id
                  LIMIT 1";
        $res = $this->client->run($query, $params);
        if ($res->count() === 0) {
            return null;
        }

        $first = $res->first();
        $ownerRaw = $first->get('owner_id');
        $ownerNeoId = $ownerRaw !== null ? (int)$ownerRaw : null;
        $collection = $this->formatCollectionRow($first->get('c'), 0, $ownerNeoId);

        // Enforce visibility.
        if ($collection['scope'] === 'personal') {
            if (!$userNeoId || $ownerNeoId !== (int)$userNeoId) {
                return null;
            }
        } elseif (!$textsArePublic && !$userNeoId) {
            return null;
        }

        [$textLabel, $textPk, $textProp] = $this->getTextConfig();
        $textsQuery = "MATCH (t:$textLabel)-[r:IN_COLLECTION]->(c:Collection)
                       WHERE id(c) = \$cNeoId
                       RETURN t, r.added_at AS added_at
                       ORDER BY t.$textPk ASC";
        $textsRes = $this->client->run($textsQuery, ['cNeoId' => $collection['neo_id']]);

        $texts = [];
        foreach ($textsRes as $row) {
            $tNode = $row['t'];
            $tProps = $tNode['properties'] ?? [];
            $textId = (int)($tProps[$textPk] ?? 0);
            $title = !empty($tProps['publication'])
                ? (string)$tProps['publication']
                : (!empty($tProps['place']) ? (string)$tProps['place'] : ('Text #' . $textId));
            $rawBody = (string)($tProps[$textProp] ?? '');
            $lang = $tProps['language'] ?? ($tProps['latte_language'] ?? null);

            $texts[] = [
                'neo_id' => (int)$tNode['id'],
                'text_id' => $textId,
                'title' => $title,
                'language' => $lang ? (string)$lang : null,
                'char_count' => mb_strlen($rawBody, 'UTF-8'),
                'added_at' => (string)($row['added_at'] ?? ''),
            ];
        }

        $collection['text_count'] = count($texts);
        $collection['texts'] = $texts;
        return $collection;
    }

    /**
     * Return all accessible collections with membership status for a specific Text Neo4j ID.
     */
    public function getCollectionsForText(int $textNeoId, ?int $userNeoId, bool $textsArePublic, ?User $user = null): array {
        [$textLabel] = $this->getTextConfig();
        $memberQuery = "MATCH (t:$textLabel)-[:IN_COLLECTION]->(c:Collection)
                        WHERE id(t) = \$textNeoId
                        RETURN id(c) AS cid";
        $memberRes = $this->client->run($memberQuery, ['textNeoId' => $textNeoId]);
        $memberIds = [];
        foreach ($memberRes as $row) {
            $memberIds[(int)$row['cid']] = true;
        }

        $all = $this->listAccessibleCollections($userNeoId, $textsArePublic);
        $result = [];
        foreach (['project', 'personal'] as $scopeGroup) {
            foreach ($all[$scopeGroup] as $col) {
                $col['is_member'] = isset($memberIds[$col['neo_id']]);
                $col['can_edit_membership'] = $user ? $this->canEditCollectionMembership($user, $col) : false;
                $col['can_edit_meta'] = $user ? $this->canEditCollectionMetadata($user, $col) : false;
                $result[] = $col;
            }
        }
        return $result;
    }

    /**
     * Return only the accessible collections that a given Text belongs to (used in /text/ and exports).
     */
    public function getMemberCollectionsForText(int $textNeoId, ?int $userNeoId, bool $textsArePublic): array {
        $all = $this->getCollectionsForText($textNeoId, $userNeoId, $textsArePublic);
        return array_values(array_filter($all, function ($col) {
            return !empty($col['is_member']);
        }));
    }

    /**
     * Compute first/prev/next/last text IDs scoped to a collection.
     */
    public function getCollectionNavigation($identifier, int $currentTextId, ?int $userNeoId, bool $textsArePublic): ?array {
        $collection = $this->getCollectionByIdentifier($identifier, $userNeoId, $textsArePublic);
        if ($collection === null) {
            return null;
        }

        $ids = array_values(array_map(function ($t) {
            return (int)$t['text_id'];
        }, $collection['texts']));

        $total = count($ids);
        $idx = array_search($currentTextId, $ids, true);
        if ($idx === false) {
            return null;
        }

        return [
            'collection' => $collection,
            'first' => $total > 0 ? $ids[0] : false,
            'prev' => $idx > 0 ? $ids[$idx - 1] : false,
            'next' => ($idx < $total - 1) ? $ids[$idx + 1] : false,
            'last' => $total > 0 ? $ids[$total - 1] : false,
            'position' => $idx + 1,
            'total' => $total,
        ];
    }

    /**
     * Create a new personal or project-level collection.
     */
    public function createCollection(User $user, string $name, string $description, string $scope): array {
        $name = trim($name);
        $description = trim($description);
        $scope = strtolower(trim($scope));

        if ($name === '') {
            throw new \InvalidArgumentException('Collection name is required.');
        }
        if (!in_array($scope, ['personal', 'project'], true)) {
            throw new \InvalidArgumentException('Invalid collection scope.');
        }
        if ($scope === 'project' && !$this->canCreateProjectCollection($user)) {
            throw new \RuntimeException('Only project leads and administrators can create project collections.');
        }
        if ($scope === 'personal' && !$this->canCreatePersonalCollection($user)) {
            throw new \RuntimeException('Authentication required to create a personal collection.');
        }

        $maxRes = $this->client->run('MATCH (c:Collection) RETURN COALESCE(MAX(c.collection_id), 0) AS max_id');
        $nextId = 1;
        if ($maxRes->count() > 0) {
            $nextId = ((int)$maxRes->first()->get('max_id')) + 1;
        }

        $uid = $this->generateUuidV4();
        $now = gmdate('c');

        $createQuery = 'CREATE (c:Collection {
                            collection_id: $cid,
                            uid: $uid,
                            name: $name,
                            description: $description,
                            scope: $scope,
                            created_at: $now,
                            updated_at: $now
                        })
                        RETURN c';
        $res = $this->client->run($createQuery, [
            'cid' => $nextId,
            'uid' => $uid,
            'name' => $name,
            'description' => $description,
            'scope' => $scope,
            'now' => $now,
        ]);

        $cNode = $res->first()->get('c');
        $cNeoId = (int)$cNode['id'];

        if ($user->neoId) {
            $relType = ($scope === 'personal') ? 'OWNS_COLLECTION' : 'priv_created';
            $this->client->run(
                "MATCH (u:priv_user), (c:Collection)
                 WHERE id(u) = \$uId AND id(c) = \$cId
                 MERGE (u)-[:$relType]->(c)",
                ['uId' => (int)$user->neoId, 'cId' => $cNeoId]
            );
        }

        return $this->formatCollectionRow(
            $cNode,
            0,
            ($scope === 'personal' && $user->neoId) ? (int)$user->neoId : null
        );
    }

    /**
     * Update name and description of an existing collection.
     */
    public function updateCollection(User $user, $identifier, string $name, string $description): bool {
        $name = trim($name);
        $description = trim($description);
        if ($name === '') {
            throw new \InvalidArgumentException('Collection name is required.');
        }

        $collection = $this->getCollectionByIdentifier($identifier, $user->neoId ?: null, true);
        if (!$collection) {
            throw new \RuntimeException('Collection not found.');
        }
        if (!$this->canEditCollectionMetadata($user, $collection)) {
            throw new \RuntimeException('Insufficient permissions to update this collection.');
        }

        $this->client->run(
            'MATCH (c:Collection) WHERE id(c) = $cId
             SET c.name = $name, c.description = $description, c.updated_at = $now',
            [
                'cId' => $collection['neo_id'],
                'name' => $name,
                'description' => $description,
                'now' => gmdate('c'),
            ]
        );
        return true;
    }

    /**
     * Delete a collection and all its membership edges.
     */
    public function deleteCollection(User $user, $identifier): bool {
        $collection = $this->getCollectionByIdentifier($identifier, $user->neoId ?: null, true);
        if (!$collection) {
            throw new \RuntimeException('Collection not found.');
        }
        if (!$this->canEditCollectionMetadata($user, $collection)) {
            throw new \RuntimeException('Insufficient permissions to delete this collection.');
        }

        $this->client->run(
            'MATCH (c:Collection) WHERE id(c) = $cId DETACH DELETE c',
            ['cId' => $collection['neo_id']]
        );
        return true;
    }

    /**
     * Add or remove a Text node from a collection.
     */
    public function setTextMembership(User $user, $identifier, int $textNeoId, bool $member): bool {
        $collection = $this->getCollectionByIdentifier($identifier, $user->neoId ?: null, true);
        if (!$collection) {
            throw new \RuntimeException('Collection not found.');
        }
        if (!$this->canEditCollectionMembership($user, $collection)) {
            throw new \RuntimeException('Insufficient permissions to modify collection membership.');
        }

        [$textLabel] = $this->getTextConfig();
        if ($member) {
            $this->client->run(
                "MATCH (t:$textLabel), (c:Collection)
                 WHERE id(t) = \$tId AND id(c) = \$cId
                 MERGE (t)-[r:IN_COLLECTION]->(c)
                 ON CREATE SET r.added_at = \$now, r.added_by = \$userId",
                [
                    'tId' => $textNeoId,
                    'cId' => $collection['neo_id'],
                    'now' => gmdate('c'),
                    'userId' => (int)$user->myId,
                ]
            );
        } else {
            $this->client->run(
                "MATCH (t:$textLabel)-[r:IN_COLLECTION]->(c:Collection)
                 WHERE id(t) = \$tId AND id(c) = \$cId
                 DELETE r",
                [
                    'tId' => $textNeoId,
                    'cId' => $collection['neo_id'],
                ]
            );
        }
        return true;
    }
}
