<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

/**
 * App\Models\Wikidata
 *
 * @property mixed data
 * @property mixed entity
 * @property string wiki_desc_lang wikipedia summary
 * @property integer id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityLog[] $activities
 */
class Wikidata extends Model implements Searchable {
    use HasTranslations;
    use LogsActivity;

    static public $defaultAvatarUrl = 'images/avatar-default.png';
    static public $endpoint = 'https://wikidata.org';

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
    public static $PIC_URL_BASE = 'https://upload.wikimedia.org/wikipedia/commons/';

    public $translatable = [
        'description_lang'
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

    protected $casts = [
        'id' => 'integer',
    ];

    public function getEntityAttribute() {
        return json_decode($this->data);
    }
    public function getClaim($prop) {
        return json_decode($this->data)->claims->$prop ?? null;
    }
    public function getLabel($locale) {
        return json_decode($this->data)->labels->$locale->value ?? '';
    }
    public function getAliases($locale) {
        return json_decode($this->data)->aliases->$locale->value ?? '';
    }
    public function getDescriptionLangAttribute() {
        $descriptionLang = [];
        foreach ($this->entity->descriptions as $locale => $description) {
            $descriptionLang[$locale] = $description->value;
        }
        return $descriptionLang;
    }

    // get wikidata description with fallback
    public function getDescription(string $locale) {
        return $this->fallback('description_lang', $locale);
    }

    public function getSiteTitle(string $locale = '', array $fallback = ['en', 'zh', 'ja', 'fr', 'de', 'es', 'pt', 'pl']) {
        $siteLinks = json_decode($this->data)->sitelinks;

        array_unshift($fallback, $locale);
        foreach ($fallback as $lang) {
            $wikiname = $lang.'wiki';
            // dd(isset($siteLinks->$wikiname), array_keys((array)$siteLinks), $siteLinks->$wikiname);

            $title = $siteLinks->$wikiname->title ?? '';
            if(!$title) continue;
            return ['title' => $title, 'locale' => $lang];
        }

        $siteNames = collect(array_keys((array)$siteLinks))->filter(function($name){
            return str_ends_with($name, 'wiki');
        });
        if(count($siteNames)) {
            $first = $siteNames[0];
            $title = $siteLinks->$first->title ?? '';
            if($title)
                return ['title' => $title, 'locale' => $lang];
        }

        return null;
    }

    public function getSiteLink(string $locale, $fallback = ['en', 'zh', 'ja', 'fr', 'de', 'es', 'pt', 'pl']) {
        $title = $this->getSiteTitle($locale, $fallback);
        if(!$title) return null;

        return 'https://'.$title['locale'].'.wikipedia.org/wiki/' . str_replace(' ', '_', $title['title']);
    }

    public function getUrlAttribute() {
        return $this->getSiteLink(config('app.locale-wikipedia'));
    }
    public function getWikidataUrlAttribute() {
        return static::$endpoint.'/wiki/Q'.$this->id;
    }

    public function getFirstPicUrlAttribute() {
        $entity = json_decode($this->data);
        if (isset($entity->claims->P18)) {
            $P18 = $entity->claims->P18;
            foreach ($P18 as $image) {
                if (!isset($image->mainsnak->datavalue->value)) {
                    continue;
                }
                $fileName = str_replace(' ', '_', $image->mainsnak->datavalue->value);
                $ab = substr(md5($fileName), 0, 2);
                $a = substr($ab, 0, 1);
                return self::$PIC_URL_BASE . $a . '/' . $ab . '/' . $fileName;
            }
        }
        return asset(static::$defaultAvatarUrl);
    }

    public function getPicUrls() {
        $entity = json_decode($this->data);
        $picUrl = [];
        if (isset($entity->claims->P18)) {
            $P18 = $entity->claims->P18;
            foreach ($P18 as $image) {
                if (!isset($image->mainsnak->datavalue->value)) {
                    continue;
                }
                $fileName = str_replace(' ', '_', $image->mainsnak->datavalue->value);
                $ab = substr(md5($fileName), 0, 2);
                $a = substr($ab, 0, 1);
                $picUrl[] = self::$PIC_URL_BASE . $a . '/' . $ab . '/' . $fileName;
            }
        }
        return $picUrl;
    }


    public function getSearchResult(): SearchResult {
        $url = '';// TODO wikidata url here
        return new SearchResult(
            $this,
            $this->getSiteTitle('zh')['title'],
            $url
        );
    }
}
