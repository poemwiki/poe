<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;

class Tag extends Model {
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'tag';

    protected $fillable = [
        'category_id',
        'describe_lang',
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
        'name_lang',

    ];

    // protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    // public function getResourceUrlAttribute() {
    //     return url('/admin/tags/' . $this->getKey());
    // }

    // tag poem M:M
}
