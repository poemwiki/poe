<?php

namespace App\Models;

use App\Traits\HasFakeId;
use App\Traits\HasTranslations;
use App\Traits\RelatableNode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

/**
 * Class List.
 *
 * @property int $id
 * @mixin \Eloquent
 */
class Collection extends Model implements Searchable {
    // use SoftDeletes;
    // use HasTranslations;
    // use HasFakeId;
    // use LogsActivity;
    // use RelatableNode;

    // protected static $logFillable             = true;
    // protected static $logOnlyDirty            = true;
    // protected static $ignoreChangedAttributes = ['created_at', 'need_confirm', 'length'];

    protected $table = 'list';

    protected $fillable = [
        'describe',
        'name',
    ];

    // protected $dates = [
    //     'created_at',
    //     'updated_at',
    // ];

    // these attributes are translatable
    // public $translatable = [
    //     'describe_lang',
    //     'name_lang',
    // ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer'
    ];

    protected $appends = [];

    public function poems() {
        return $this->hasManyThrough(\App\Models\Poem::class, \App\Models\CollectionPoem::class,
            'list_id', 'id', 'id', 'poem_id');
    }

    public function collections() {
        return $this->hasMany(\App\Models\Collection::class, 'list_id', 'id');
    }

    /**
     * TODO move this to Query service
     * search poems within this collection.
     */
    public static function searchPoems() {
    }

    public function getSearchResult(): SearchResult {
        $url = route('collection/show', ['id' => $this->id]);

        return new SearchResult(
            $this,
            $this->name,
            $url
        );
    }
}
