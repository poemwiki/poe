<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $table = 'score';

    protected $fillable = [
        'content_id',
        'factor',
        'poem_id',
        'score',
        'user_id',

    ];


    protected $dates = [
        'created_at',
        'updated_at',

    ];

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/scores/'.$this->getKey());
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function originalPoem() {
        return $this->belongsTo(\App\Models\Poem::class, 'poem_id', 'id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function content() {
        return $this->hasOne(\App\Models\Content::class, 'id', 'content_id');
    }
}
