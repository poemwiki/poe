<?php

namespace App\Console\Wiki;

use App\Models\Wikidata;
use App\Services\WikiDataFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLanguage extends Command {
    protected $signature   = 'wiki:importLanguage {wikidataId}';
    protected $description = 'Import a language into language table by Wikidata id.';

    public function handle() {
        $wikidataId = (int)$this->argument('wikidataId');

        if ($wikidataId <= 0) {
            $this->error('wikidataId argument is required.');

            return 1;
        }

        $entity = $this->loadWikidataEntity($wikidataId);
        if (!$entity) {
            $this->error('Wikidata entity not found.');

            return 1;
        }

        $labels = $this->extractLabels($entity);
        if (empty($labels)) {
            $this->error('No labels found on Wikidata entity.');

            return 1;
        }

        $code = $this->resolveLanguageCode($entity->claims ?? null);
        if (!$code) {
            $this->error('Language code not found in wikidata claims.');

            return 1;
        }

        $nameCn = $labels['zh-cn'] ?? $labels['zh'] ?? $labels['zh-hans'] ?? $labels['en'] ?? $code;
        if ($nameCn === '') {
            $this->error('Chinese label is required to import language.');

            return 1;
        }

        if (DB::table('language')
            ->where('locale', $code)
            ->orWhere('wikidata_id', $wikidataId)
            ->exists()) {
            $this->info('Language already exists, skipping.');

            return 0;
        }

        $insertData = [
            'locale'      => $code,
            'wikidata_id' => $wikidataId,
            'name'        => '', // set name to $labels[$code] to enable this language
            'name_cn'     => $nameCn,
            'name_lang'   => json_encode((object)$labels, JSON_UNESCAPED_UNICODE),
            'sort_order'  => 999,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];

        $newId = DB::table('language')->insertGetId($insertData);

        // set sort_order to match id by default
        DB::table('language')->where('id', $newId)->update(['sort_order' => $newId]);

        $this->info("Language {$code} imported.");

        return 0;
    }

    private function loadWikidataEntity(int $wikidataId): ?object {
        $wikidata = Wikidata::find($wikidataId);
        if ($wikidata) {
            $decoded = json_decode($wikidata->data ?? '');
            if ($decoded) {
                return $decoded;
            }
        }

        $entity = WikiDataFetcher::fetchEntity($wikidataId);
        if ($entity) {
            Wikidata::updateOrCreate(
                ['id' => $wikidataId],
                ['type' => Wikidata::TYPE['language'], 'data' => json_encode($entity, JSON_UNESCAPED_UNICODE)]
            );
        }

        return $entity;
    }

    private function extractLabels(object $entity): array {
        $labels = [];
        if (!isset($entity->labels)) {
            return $labels;
        }

        foreach ($entity->labels as $locale => $label) {
            if (isset($label->value)) {
                $labels[$locale] = trim($label->value);
            }
        }

        return $labels;
    }

    private function resolveLanguageCode(?object $claims): ?string {
        if (!$claims) {
            return null;
        }

        foreach (['P424', 'P218', 'P220', 'P9060', 'P305'] as $property) {
            $value = $this->firstClaimString($claims, $property);
            if ($value !== null) {
                return strtolower($value);
            }
        }

        return null;
    }

    private function firstClaimString(object $claims, string $property): ?string {
        if (!isset($claims->{$property})) {
            return null;
        }

        foreach ($claims->{$property} as $statement) {
            $snak = $statement->mainsnak ?? null;
            if ($snak && isset($snak->datavalue->value) && is_string($snak->datavalue->value)) {
                return $snak->datavalue->value;
            }
        }

        return null;
    }
}
