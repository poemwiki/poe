<?php

namespace App\Models;

use Brackets\Translatable\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Wikidata extends Model {
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

    use HasTranslations;
    public $translatable = [
        'label_lang',
    ];

    // TODO log activity for command execution
    use LogsActivity;
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
