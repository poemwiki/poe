<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Nation.
 *
 * @property int                             $id
 * @property array                           $name_lang
 * @property int                             $f_id
 * @property int|null                        $wikidata_id
 * @property array|null                      $describe_lang
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property mixed                           $resource_url
 * @property array                           $translations
 */
class Nation extends Model {
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'nation';

    protected $fillable = [
        'describe_lang',
        'f_id',
        'name_lang',
        'wikidata_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
    // these attributes are translatable
    public $translatable = [
        'describe_lang',
        'name_lang',
    ];

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/nations/' . $this->getKey());
    }
}
