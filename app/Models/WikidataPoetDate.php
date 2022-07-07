<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikidataPoetDate extends Model {
    // use HasTranslations;

    protected $table = 'wikidata_poet_date';

    // public $translatable = [
    // ];

    protected $dates = [
        // 'birth_date', // it's not valid date if birth_time_precision <= 11
        // 'death_date' // it's not valid date if death_time_precision <= 11
    ];

    protected $fillable = [
    ];

}
