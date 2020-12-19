<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;

class Dynasty extends Model
{
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
        'deleted_at',
        'updated_at',

    ];
    // these attributes are translatable
    public $translatable = [
        'describe_lang',
        'name_lang',

    ];

    protected $appends = ['resource_url', 'children'];


    public function children() {
        return $this->hasMany(\App\Models\Dynasty::class, 'f_id', 'id');
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/dynasties/'.$this->getKey());
    }
}
