<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Brackets\Translatable\Traits\HasTranslations;

class Genre extends Model
{
    use SoftDeletes;
use HasTranslations;
    protected $table = 'genre';

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
    
    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/genres/'.$this->getKey());
    }
}
