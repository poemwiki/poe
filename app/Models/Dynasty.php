<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Dynasty.
 *
 * @property int                                                $id
 * @property string                                             $name
 * @property array                                              $name_lang
 * @property int                                                $f_id
 * @property int|null                                           $wikidata_id
 * @property array|null                                         $describe_lang
 * @property \Illuminate\Support\Carbon|null                    $created_at
 * @property \Illuminate\Support\Carbon|null                    $updated_at
 * @property \Illuminate\Support\Carbon|null                    $deleted_at
 * @property string|null                                        $import_id
 * @property \Illuminate\Database\Eloquent\Collection|Dynasty[] $children
 * @property int|null                                           $children_count
 * @property mixed                                              $resource_url
 * @property array                                              $translations
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty newQuery()
 * @method static \Illuminate\Database\Query\Builder|Dynasty onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty query()
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereDescribeLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereFId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereImportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereNameLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Dynasty whereWikidataId($value)
 * @method static \Illuminate\Database\Query\Builder|Dynasty withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Dynasty withoutTrashed()
 * @mixin \Eloquent
 */
class Dynasty extends Model {
    use SoftDeletes;
    use HasTranslations;
    protected $table = 'dynasty';

    protected $fillable = [
        'describe_lang',
        'f_id',
        'name',
        'name_lang',
        'wikidata_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];
    // these attributes are translatable
    public $translatable = [
        'describe_lang',
        'name_lang',
    ];

    protected $appends = ['resource_url'];

    public function children() {
        return $this->hasMany(\App\Models\Dynasty::class, 'f_id', 'id');
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/dynasties/' . $this->getKey());
    }
}
