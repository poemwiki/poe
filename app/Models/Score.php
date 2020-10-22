<?php

namespace App\Models;

use App\Traits\HasCompositeKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Score extends Model
{
    use SoftDeletes;
    // use LogsActivity; // TODO enable LogsActiivity cause error for this composite primary key model
    use HasCompositeKey;
    protected $table = 'score';
    protected $primaryKey = ['poem_id', 'user_id'];
    public $incrementing = false;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

    protected $fillable = [
        'poem_id',
        'score',
        'user_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/scores/'.$this->getKey());
    }
    /**
     * @return string
     */
//    public function getUrlAttribute() {
//        return route('score/show', ['id' => $this->id]);
//    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function poem() {
        return $this->belongsTo(\App\Models\Poem::class, 'poem_id', 'id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function content() {
        return $this->hasOne(\App\Models\Content::class, 'id', 'content_id');
    }
}
