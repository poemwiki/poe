<?php

namespace App\Models;

use App\Traits\HasFakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Author extends Model implements Searchable {
    use SoftDeletes;
    use HasTranslations;
    use HasFakeId;

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

    public function poems() {
        return $this->hasMany(\App\Models\Poem::class, 'poet_id', 'id');
    }
    public function translatedPoems() {
        return $this->hasMany(\App\Models\Poem::class, 'translator_id', 'id');
    }
    public function user() {
        return $this->hasOne(\App\User, 'id', 'user_id');
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/authors/' . $this->getKey());
    }
    /**
     * @return string
     */
    public function getUrlAttribute() {
        return route('author/show', ['fakeId' => $this->fakeId]);
    }

    public static function searchPoems() {

    }

    public function getSearchResult(): SearchResult {
        $url = route('author/show', ['fakeId' => $this->fakeId]);

        return new SearchResult(
            $this,
            $this->name_lang,
            $url
        );
    }
}
