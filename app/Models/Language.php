<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\Language.
 *
 * @property int                                                                $id
 * @property string                                                             $name
 * @property string                                                             $name_cn
 * @property \Illuminate\Support\Carbon|null                                    $created_at
 * @property \Illuminate\Support\Carbon|null                                    $updated_at
 * @property \Illuminate\Support\Carbon|null                                    $deleted_at
 * @property array                                                              $name_lang
 * @property string                                                             $locale
 * @property string|null                                                        $pic_url
 * @property int|null                                                           $wikidata_id
 * @property mixed|null                                                         $wikipedia_url
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityLog[] $activities
 * @property int|null                                                           $activities_count
 * @property mixed|string                                                       $label
 * @property mixed                                                              $label_cn
 * @property mixed                                                              $label_en
 * @property array                                                              $translations
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Poem[]        $poems
 * @property int|null                                                           $poems_count
 * @method static \Illuminate\Database\Eloquent\Builder|Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language newQuery()
 * @method static \Illuminate\Database\Query\Builder|Language onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Language query()
 * @method static \Illuminate\Database\Eloquent\Builder|Language where()
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereNameCn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereNameLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language wherePicUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereWikidataId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereWikipediaUrl($value)
 * @method static \Illuminate\Database\Query\Builder|Language withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Language withoutTrashed()
 * @method Language inUse()
 * @mixin \Eloquent
 */
class Language extends Model {
    use SoftDeletes;
    use HasTranslations;
    use LogsActivity;

    const LANGUAGE_ID = [
        'ZH' => 1, // zh_CN
        'EN' => 2,
    ];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['created_at', 'updated_at']);
    }

    public $table = 'language';

    protected $dates = [
        'created_at',
        'updated_at'
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
        'id'      => 'integer',
        'name'    => 'string',
        'name_cn' => 'string'
    ];

    /**
     * Validation rules.
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
     * TODO move these label methods to HasLabel Trait.
     * @return mixed|string
     */
    public function getLabelAttribute() {
        $default  = $this->getTranslated('name_lang', config('app.locale'));
        $fallback = $this->name;
        if ($default !== $fallback && $fallback) {
            return $default . " ($fallback)";
        }

        return $default ?: $fallback;
    }

    public function getLabelEnAttribute() {
        return $this->getTranslated('name_lang', 'en');
    }

    public function getLabelCnAttribute() {
        return $this->getTranslated('name_lang', 'zh-CN');
    }

    public function scopeInUse($query) {
        return $query->where('name', '<>', '');
    }
}
