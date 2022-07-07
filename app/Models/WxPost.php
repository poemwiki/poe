<?php

namespace App\Models;

use Eloquent as Model;

class WxPost extends Model {

    public $table = 'wx_post';
    public $timestamps = false;

    public $fillable = [
        'title' => 'string',
        'digest' => 'string',
        'link' => 'string',
        'short_url'
    ];

    public $primaryKey = 'aid';
    public $incrementing = false;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'aid' => 'string',
        'title' => 'string',
        'digest' => 'string',
        'link' => 'string',
        'short_url' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
//    public function poems() {
//        return $this->hasOne(\App\Models\Poem::class, 'id', 'poem_id');
//    }
}
