<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\Genre
 *
 * @property int $id
 * @property string $name
 * @property array $name_lang
 * @property int $f_id
 * @property int|null $wikidata_id
 * @property array|null $describe_lang
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityLog[] $activities
 * @property-read int|null $activities_count
 * @property-read mixed $resource_url
 * @property-read array $translations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Poem[] $poems
 * @property-read int|null $poems_count
 * @method static \Illuminate\Database\Eloquent\Builder|Genre newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Genre newQuery()
 * @method static \Illuminate\Database\Query\Builder|Genre onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Genre query()
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereDescribeLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereFId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereNameLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Genre whereWikidataId($value)
 * @method static \Illuminate\Database\Query\Builder|Genre withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Genre withoutTrashed()
 * @mixin \Eloquent
 */
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
