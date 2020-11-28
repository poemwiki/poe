<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Genre extends Model {
    use SoftDeletes;
    use HasTranslations;
    use LogsActivity;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

    public $table = 'genre';

    protected $fillable = [
        'describe_lang',
        'f_id',
        'name',
        'name_lang',
        'wikidata_id',

    ];


    protected $dates = [
        'created_at',
        'deleted_at',
        'updated_at',

    ];
    // these attributes are translatable
    public $translatable = [
        'describe_lang',
        'name_lang'
    ];

    protected $appends = ['resource_url'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function poems() {
        return $this->hasMany(\App\Models\Poem::class, 'genre_id', 'id');
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/genres/' . $this->getKey());
    }

    public static function ids() {
        return self::select('id')->get()->pluck('id');
    }
}
