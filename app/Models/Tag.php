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


    protected $casts = [
        'id' => 'integer',
        'wikidata_id' => 'integer',
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

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/tags/' . $this->getKey());
    }

    // tag poem M:M
    public function poems() {
        return $this->morphedByMany(\App\Models\Poem::class, 'taggable', 'taggable');
    }

    public function campaign() {
        return $this->hasOne(\App\Models\Campaign::class, 'tag_id', 'id');
    }
}
