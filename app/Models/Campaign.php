<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;

class Campaign extends Model {
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'campaign';

    protected $fillable = [
        'tag_id',
        'describe_lang',
        'name',
        'name_lang',
        'start',
        'end',
        'image',
        'settings'
    ];


    protected $dates = [
        'created_at',
        'deleted_at',
        'updated_at',
        'start'

    ];
    // these attributes are translatable
    public $translatable = [
        'describe_lang',
        'name_lang',
    ];

    public $casts = [
        'settings' => 'json'
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['tag'];

    protected $appends = ['tag_name', 'image_url', 'masters'];

    /* ************************ ACCESSOR ************************* */

    // public function getUrlAttribute() {
    //     return url('/campaign/' . $this->getKey());
    // }
    public function getTagNameAttribute() {
        return $this->tag->name_lang;
    }
    public function getImageUrlAttribute() {
        return asset($this->image);
    }
    public function getMastersAttribute() {
        $masters = $this->settings['masters'];
        if(!$masters) return null;

        return array_map(function ($item) {
            $item['avatar'] = asset($item['avatar']);
            return $item;
        }, $masters);
    }

    public function tag() {
        return $this->belongsTo(\App\Models\Tag::class, 'tag_id', 'id');
    }
}
