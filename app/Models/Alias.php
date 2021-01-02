<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;

class Alias extends Model {
    protected $table = 'alias';

    protected $fillable = [
        'name',
        'locale',
        'language_id',
        'wikidata_id',
        'author_id'

    ];


    protected $dates = [
        'created_at',
        'updated_at',

    ];

    protected $appends = ['QID', 'url', 'label', 'label_en', 'label_cn'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/alias/' . $this->getKey());
    }
    public function getUrlAttribute() {
        return $this->wikidata->getSiteLink(app()->getLocale() === 'en' ? 'en' : 'zh');
    }

    // todo label should be name(current locale name with same wikidata_id)
    public function getLabelAttribute() {
        $cn = $this->label_cn;
        return ($cn !== $this->name && $cn) ? "{$this->name}（{$cn}）" : $this->name;
    }
    public function getLabelEnAttribute() {
        return $this->wikidata->getLabel('en');
    }
    public function getLabelCnAttribute() {
        return $this->wikidata->getLabel('zh') ?? $this->wikidata->getLabel('zh-hant') ?? $this->wikidata->getLabel('lzh');
    }
    public function getQIDAttribute() {
        return 'Q'.$this->id;
    }

    public function wikidata() {
        return $this->hasOne(\App\Models\Wikidata::class, 'id', 'wikidata_id');
    }


}
