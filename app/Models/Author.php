<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;

class Author extends Model {
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'author';

    protected $fillable = [
        'describe_lang',
        'name_lang',
        'pic_url',
        'user_id',
        'wikidata_id',
        'wikipedia_url',

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


    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'wikidata_id' => 'integer',
        'user_id' => 'integer',
        'pic_url' => 'json'
    ];

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/authors/' . $this->getKey());
    }
}
