<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Tag.
 *
 * @property int                                                         $id
 * @property string                                                      $name
 * @property array                                                       $name_lang
 * @property int|null                                                    $wikidata_id
 * @property array|null                                                  $describe_lang
 * @property int|null                                                    $category_id
 * @property \Illuminate\Support\Carbon|null                             $created_at
 * @property \Illuminate\Support\Carbon|null                             $updated_at
 * @property \Illuminate\Support\Carbon|null                             $deleted_at
 * @property \App\Models\Campaign|null                                   $campaign
 * @property mixed                                                       $is_campaign
 * @property mixed                                                       $resource_url
 * @property array                                                       $translations
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Poem[] $poems
 * @property int|null                                                    $poems_count
 */
class Tag extends Model {
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'tag';

    protected $fillable = [
        'category_id',
        'describe_lang',
        'name',
        'name_lang',
        'wikidata_id',
    ];

    protected $casts = [
        'id'          => 'integer',
        'wikidata_id' => 'integer',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
    // these attributes are translatable
    public $translatable = [
        'describe_lang',
        'name_lang',
    ];

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/tags/' . $this->getKey());
    }

    public function getIsCampaignAttribute() {
        return $this->category_id === Category::$CATEGORY_ID['campaign'];
    }

    // tag poem M:M
    public function poems() {
        return $this->morphedByMany(\App\Models\Poem::class, 'taggable', 'taggable');
    }

    // TODO if category_id is not 2, this should return null
    public function campaign() {
        return $this->hasOne(\App\Models\Campaign::class, 'tag_id', 'id');
    }
}
