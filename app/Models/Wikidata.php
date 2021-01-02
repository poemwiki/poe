<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Wikidata extends Model {
    use HasTranslations;
    use LogsActivity;

    protected $table = 'wikidata';
    // '0':poet, '1':nation/region/country of citizenship,
    // '2':language/locale, '3':genre',
    // '4':dynasty"?
    const TYPE = [
        'poet' => '0',
        'country' => '1',
        'language' => '2',
        'genre' => '3',
    ];
    const PROP = [
        'birthdate' => 'P569',
        'deathdate' => 'P570',
        'gender' => 'P21',
        'countries' => 'P27',
        'occupations' => 'P106',
        'languages' => 'P1412',
        'time_period' => 'P2348',
        'native_language' => 'P103',
        'native_label' => 'P1705',
        'name_in_native_language' => 'P1559',
        'named as' => 'P1810', // name by which a subject is recorded in a database or mentioned as a contributor of a work
        'images' => 'P18'
    ];
    const LOCALE_FALLBACK = [
        'zh' => ['zh', 'zh-cn', 'zh-hans', 'zh-Hans-CN', 'zh-hant', 'zh-hk', 'zh-tw', 'zh-yue', 'zh-sg'],
        'zh-CN' => ['zh-hant', 'zh-hk', 'zh-tw', 'zh-yue', 'zh', 'zh-cn', 'zh-hans', 'zh-Hans-CN', 'zh-sg']
    ];
    const PIC_URL_BASE = 'https://upload.wikimedia.org/wikipedia/commons/';

    public $translatable = [
    ];

    // TODO log activity for command execution
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'id',
        'data',
        'type',
        'label_lang',
    ];

    public function getClaim($prop) {
        return json_decode($this->data)->claims->$prop ?? null;
    }
    public function getLabel($locale) {
        return json_decode($this->data)->labels->$locale->value ?? '';
    }
    public function getAliases($locale) {
        return json_decode($this->data)->aliases->$locale->value ?? '';
    }
    public function getSiteLink($locale) {
        $wikiname = $locale.'wiki';
        $title = json_decode($this->data)->sitelinks->$wikiname->title ?? '';
        if(!$title) return '';
        return 'https://'.$locale.'.wikipedia.org/wiki/' . str_replace(' ', '_', $title);
    }
    public function getUrlAttribute() {
        return $this->getSiteLink(app()->getLocale() === 'en' ? 'en' : 'zh');
    }

}
