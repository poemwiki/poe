<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Wikidata extends Model {
    use HasTranslations;
    use LogsActivity;

    protected $table = 'wikidata';
    // '0':poet, '1':nation/region/country of citizenship,
    // '2':language/locale, '3':genre',
    // '4':dynasty"?
    const TYPE = [
        'poet' => '0',
        'country' => '1',
        'language' => '2',
        'genre' => '3',
    ];
    const PROP = [
        'birthdate' => 'P569',
        'deathdate' => 'P570',
        'gender' => 'P21',
        'countries' => 'P27',
        'occupations' => 'P106',
        'languages' => 'P1412',
        'time_period' => 'P2348',
        'native_label' => 'P1705',
        'images' => 'P18'
    ];

    public $translatable = [
    ];

    // TODO log activity for command execution
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'id',
        'data',
        'type',
        'label_lang',
    ];

}
