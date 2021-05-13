<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string name
 */
class Language extends Model {
    use SoftDeletes;
    use HasTranslations;
    use LogsActivity;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

    public $table = 'language';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];


    public $fillable = [
        'name',
        'name_cn',
        'name_lang',
        'locale',
        'wikidata_id',
        'wikipedia_url',
        'pic_url',
    ];
    // these attributes are translatable
    public $translatable = [
        'name_lang'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'name_cn' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function poems() {
        return $this->hasMany(\App\Models\Poem::class, 'language_id', 'id');
    }

    /**
     * TODO move these label methods to HasLabel Trait
     * @return mixed|string
     */
    public function getLabelAttribute() {
        $default = $this->getTranslated('name_lang', config('app.locale'));
        $fallback = $this->name;
        if ($default !== $fallback && $fallback) {
            return  $default." ($fallback)";
        }
        return $default ?: $fallback;
    }
    public function getLabelEnAttribute() {
        return $this->getTranslated('name_lang', 'en');
    }
    public function getLabelCnAttribute() {
        return $this->getTranslated('name_lang', 'zh-CN');
    }

}
