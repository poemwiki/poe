<?php

namespace App\Models;

use App\Traits\HasFakeId;
use App\Traits\HasTranslations;
use App\Traits\RelatableNode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Class List.
 *
 * @property int $id
 * @mixin \Eloquent
 */
class CollectionPoem extends Model {
    // use SoftDeletes;
    // use HasTranslations;
    // use HasFakeId;
    // use LogsActivity;
    // use RelatableNode;

    // protected static $logFillable             = true;
    // protected static $logOnlyDirty            = true;
    // protected static $ignoreChangedAttributes = ['created_at', 'need_confirm', 'length'];

    protected $table = 'list_poem';

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

    public function poem() {
        return $this->hasOne(Poem::class, 'id', 'poem_id');
    }
}
