<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class importLanguage extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'language:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import language table from json file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * download language.json from https://query.wikidata.org, using SQL below:
     * ```SQL
     * SELECT distinct ?item ?code ?entity_id ?lang (MAX(?label) AS ?val) #?itemLabel_zh
     * WHERE {
     * #BIND(wd:Q9067 AS ?item)
     * ?item wdt:P31 wd:Q34770.  # 选择所有语言的条目，这里假设Q34770是语言的类别
     *
     * OPTIONAL {
     * ?item wdt:P305 ?code. # IETF language tag
     * }
     * OPTIONAL {
     * ?item wdt:P218 ?code. # ISO 639-1 代码
     * }
     * OPTIONAL {
     * ?item wdt:P9060 ?code. # POSIX locale
     * }
     * FILTER(BOUND(?code))  # 仅选择具有至少一个非空code的条目
     *
     * #OPTIONAL {
     * #  ?item rdfs:label ?itemLabel_zh filter (lang(?itemLabel_zh) = "zh").
     * #}
     *
     * # 获取条目的各种语言写法
     * ?item rdfs:label ?label.
     * BIND(LANG(?label) AS ?lang).
     * BIND(STRAFTER(STR(?item), "http://www.wikidata.org/entity/Q") AS ?entity_id).
     * }
     * GROUP BY ?item ?code ?entity_id ?lang #?itemLabel_zh
     * ORDER BY ?code ?lang
     * # LIMIT 30
     * ```
     * @return int
     */
    public function handle() {
        $languages = json_decode(file_get_contents(storage_path('/language_wikidata.json')));

        $entities     = [];
        $translations = [];
        foreach ($languages as $record) {
            if (!isset($entities[$record->code])) {
                $entities[$record->code] = [];
            }

            $entities[$record->code] = [
                'locale'      => $record->code,
                'wikidata_id' => $record->entity_id
            ];

            $translations[$record->code][$record->lang] = $record->val;
            if ($record->lang === $record->code) {
                $entities[$record->code]['name'] = $record->val;
            }
        }

        foreach ($entities as $code => $entity) {
            $insertData            = $entity;
            $insertData['name']    = ''; // set name to $translations[$code][$code] to enable this language
            $insertData['name_cn'] = $translations[$code]['zh-cn'] ?? $translations[$code]['zh'] ?? '';

            if (!$insertData['name_cn']) {
                continue;
            }

            $insertData['name_lang'] = json_encode((object)$translations[$code]);

            if (DB::table('language')->where('locale', $code)->exists()) {
                continue;
            }

            DB::table('language')->insert($insertData);
        }

        return 0;
    }
}