<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Wikidata;

class CreateWikidataDateView extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::statement($this->dropView());
        DB::statement($this->createView());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        $this->dropView();
    }

    private function dropView(): string {
        return <<<SQL
DROP VIEW IF EXISTS `wikidata_poet_date`;
SQL;
    }

    private function createView(): string {
        $type = Wikidata::TYPE['poet'];
        return <<<SQL
CREATE VIEW `wikidata_poet_date` AS
SELECT * FROM (SELECT
	id,
		CASE
	WHEN name_zh IS NOT NULL THEN
		name_zh
	WHEN name_zhcn IS NOT NULL THEN
		name_zhcn
	WHEN name_hanscn IS NOT NULL THEN
		name_hanscn
	WHEN name_hant IS NOT NULL THEN
		name_hant
	WHEN name_hk IS NOT NULL THEN
		name_hk
	WHEN name_tw IS NOT NULL THEN
		name_tw
	ELSE
		name_yue
END  as name_cn,

	substr( `t`.`birth_time`, 2, `birth_time_precision`-1 ) AS `birth_date`,
	`birth_time_precision`,
	substr( `t`.`birth_time`, 2, 4 ) AS `birth_year`,
	substr( `t`.`birth_time`, 7, 2 ) AS `birth_month`,
	substr( `t`.`birth_time`, 10, 2 ) AS `birth_day`,
	substr( `t`.`death_time`, 2, `death_time_precision`-1 ) AS `death_date`,
	`death_time_precision`,
	substr( `t`.`death_time`, 2, 4 ) AS `death_year`,
	substr( `t`.`death_time`, 7, 2 ) AS `death_month`,
	substr( `t`.`death_time`, 10, 2 ) AS `death_day`,
	name_en,
	`images`
FROM
	(
	SELECT
		id,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels.zh.value' )) AS `name_zh`,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels."zh-cn".value' )) AS `name_zhcn`,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels."zh-hans".value' )) AS `name_hans`,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels."zh-Hans-CN".value' )) AS `name_hanscn`,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels."zh-hant".value' )) AS `name_hant`,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels."zh-hk".value' )) AS `name_hk`,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels."zh-tw".value' )) AS `name_tw`,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels."zh-yue".value' )) AS `name_yue`,
		json_unquote(json_extract( `wikidata`.`data`, '$.labels.en.value' )) AS `name_en`,
		json_unquote(json_extract( `wikidata`.`data`, '$.claims.P569[0].mainsnak.datavalue.value.time' )) AS `birth_time`,
		json_unquote(json_extract( `wikidata`.`data`, '$.claims.P569[0].mainsnak.datavalue.value.precision' )) AS `birth_time_precision`,
		json_unquote(json_extract( `wikidata`.`data`, '$.claims.P570[0].mainsnak.datavalue.value.time' )) AS `death_time`,
		json_unquote(json_extract( `wikidata`.`data`, '$.claims.P570[0].mainsnak.datavalue.value.precision' )) AS `death_time_precision`,
		json_extract( `wikidata`.`data`, '$.claims.P18' ) AS `images`
	FROM
		`wikidata`
WHERE
	`type` = '0'
) `t` WHERE  (`birth_time_precision`>=11 OR `death_time_precision`>=11)
) `t1`
WHERE name_cn is NOT NULL
SQL;
    }
}
