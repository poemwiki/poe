<?php

namespace App\Http\Controllers;

use App\Console\Commands\SPARQLQueryDispatcher;
use App\Models\WikidataPoetDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CalendarController extends Controller {

    // public function __construct() {
    // }
    public function index() {
        return view('calendar.index');
    }


    public function query($month, $day, $offset = 0, $force = 0) {
        $key = "calendar-query-$month-$day-$offset";
        if(!$force) {
            $cache = Cache::get($key);
            if($cache) return $cache;
        }

        $json = $this->json([
            'birth' => $this->queryDate($month, $day, 'birth', $offset = 0),
            'death' => $this->queryDate($month, $day, 'death', $offset = 0)
        ]);
        Cache::put($key, $json, now()->addMinutes(60 * 24 * 30));
        return $json;
    }

    /**
     * @param $month
     * @param $day
     * @param $type
     * @param int $offset
     * @return array|false|string
     * SELECT id,JSON_EXTRACT(`data`, "$.claims.P569") from wikidata as w
     * WHERE JSON_EXTRACT(`data`, "$.claims.P569") like "%11-09T%" limit 10
     */
    public function queryDate($month, $day, $type, $offset = 0) {
        $yearField = $type."_year";
        $monthField = $type."_month";
        $dayField = $type."_day";
        $precisionField = $type."_time_precision";
        $data = WikidataPoetDate::where([
            $monthField => sprintf('%02d', $month),
            $dayField => sprintf('%02d', $day),
            $precisionField => 11
        ])
            ->orderBy($yearField, 'DESC')
            ->limit(20)->offset($offset)
            ->get();

        return $data;
    }
}
