<?php

namespace App\Models;

use App\Repositories\ScoreRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Score extends Model {
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'score';

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];
    // public static $RATING = [1, 2, 3, 4, 5];
    public static $SCORE = [2, 4, 6, 8, 10];
    // public static $RATING_TO_SCORE = 2;

    protected $fillable = [
        'poem_id',
        'score',
        'user_id',
        'weight'
    ];


    protected $dates = [
        'created_at',
        'deleted_at',
        'updated_at',

    ];

    protected $appends = [];


    public static function boot() {
        parent::boot();

        self::created(function ($model) {
            $poem = Poem::find($model->poem_id);
            $poem->timestamps = false;
            $score = ScoreRepository::calc($model->poem_id);
            $poem->score = $score['score'] ?: null;
            $poem->save();
        });

        self::updated(function ($model) {
            $poem = Poem::find($model->poem_id);
            $poem->timestamps = false;
            $score = ScoreRepository::calc($model->poem_id);
            $poem->score = $score['score'] ?: null;
            $poem->save();
        });

        self::deleted(function ($model) {
            $poem = Poem::find($model->poem_id);
            $poem->timestamps = false;
            $score = ScoreRepository::calc($model->poem_id);
            $poem->score = $score['score'] ?: null;
            $poem->save();
        });
    }


    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/scores/' . $this->getKey());
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
