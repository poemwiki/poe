<?php

namespace App\Services;

use App\Models\Wikidata;
use Illuminate\Support\Facades\Http;

class WikiDataFetcher {
    const API_BASE_URL = 'https://www.wikidata.org/w/api.php';
    /**
     * Fetch a single Wikidata entity by numeric id (Q{id}).
     * Backwards-compatible wrapper around fetchEntities.
     *
     * @return object|null
     */
    public static function fetchEntity(int $wikidataId): ?object {
        $result = static::fetchEntities([$wikidataId]);

        return $result[$wikidataId] ?? null;
    }

    /**
     * Fetch multiple Wikidata entities by numeric ids.
     * Returns an associative array mapping numeric id => entity object.
     * Cached entries from `wikidata` table are used when available and
     * missing ones are fetched from the Wikidata API and persisted.
     *
     * @param int[] $wikidataIds
     * @return array<int,object>
     */
    public static function fetchEntities(array $wikidataIds): array {
        $ids = array_values(array_filter(array_map('intval', $wikidataIds), function ($i) { return $i > 0; }));
        if (empty($ids)) {
            return [];
        }

        // First try cached rows
        $cached  = Wikidata::whereIn('id', $ids)->get()->keyBy('id');
        $results = [];
        $missing = [];

        foreach ($ids as $id) {
            if (isset($cached[$id])) {
                $decoded = json_decode($cached[$id]->data ?? '');
                if ($decoded) {
                    $results[$id] = $decoded;

                    continue;
                }
            }
            $missing[] = $id;
        }

        if (!empty($missing)) {
            // Build Q...|Q... list
            $qIds = implode('|', array_map(function ($i) { return 'Q' . $i; }, $missing));

            $response = Http::withOptions([])
                ->withHeaders([
                    'User-Agent' => 'PoemWiki-bot/0.1 (https://poemwiki.org; poemwiki@126.com) PHP/' . PHP_VERSION,
                ])
                ->timeout(10)->retry(2, 10)->get(
                    self::API_BASE_URL,
                    [
                        'action' => 'wbgetentities',
                        'ids'    => $qIds,
                        'format' => 'json',
                    ]
                );

            if ($response->ok()) {
                $payload = $response->json();
                if (isset($payload['entities']) && is_array($payload['entities'])) {
                    foreach ($payload['entities'] as $key => $entity) {
                        // key is like 'Q123'
                        if (is_string($key) && strtoupper($key[0]) === 'Q') {
                            $numeric = (int)substr($key, 1);
                            if ($numeric > 0) {
                                $obj               = json_decode(json_encode($entity));
                                $results[$numeric] = $obj;
                                // persist
                                Wikidata::updateOrCreate(
                                    ['id' => $numeric],
                                    ['data' => json_encode($entity, JSON_UNESCAPED_UNICODE)]
                                );
                            }
                        }
                    }
                }
            }
        }

        return $results;
    }
}
