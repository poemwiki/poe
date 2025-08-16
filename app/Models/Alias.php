<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Alias
 *
 * @property int                             $id
 * @property int                             $fid
 * @property string                          $name
 * @property string|null                     $locale
 * @property int|null                        $author_id
 * @property int|null                        $language_id
 * @property int|null                        $wikidata_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Author|null $author
 * @property-read mixed $label
 * @property-read mixed $label_cn
 * @property-read mixed $label_en
 * @property-read mixed $q_i_d
 * @property-read mixed $resource_url
 * @property-read mixed $url
 * @property-read \App\Models\Wikidata|null $wikidata
 * @method static \Illuminate\Database\Eloquent\Builder|Alias newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Alias newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Alias query()
 * @method static \Illuminate\Database\Eloquent\Builder|Alias whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Alias whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Alias whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Alias whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Alias whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Alias whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Alias whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Alias whereWikidataId($value)
 * @mixin \Eloquent
 */
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


    public function author() {
        return $this->belongsTo(Author::class, 'author_id', 'id');
    }
    public function translatedFrom() {
        return $this->belongsTo(self::class, 'fid', 'id');
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/alias/' . $this->getKey());
    }
    public function getUrlAttribute() {
        return $this->wikidata ? $this->wikidata->getSiteLink(app()->getLocale() === 'en' ? 'en' : 'zh') : null;
    }

    // todo label should be name(current locale name with same wikidata_id)
    public function getLabelAttribute() {
        $authorMainName = $this->author ? ($this->author->name_lang ?: $this->author->getTranslated('name_lang', 'zh')) : null;

        return ($authorMainName !== $this->name && $authorMainName)
            ? "{$authorMainName}（{$this->name}）"
            : $this->name;
    }
    public function getLabelEnAttribute() {
        return $this->wikidata ? ($this->wikidata->getLabel('en') ?? null) : null;
    }
    public function getLabelCnAttribute() {
        return $this->wikidata
            // TODO use fallback list here
            ? ($this->wikidata->getLabel('zh-CN') ?? $this->wikidata->getLabel('zh') ?? $this->wikidata->getLabel('zh-hant') ?? $this->wikidata->getLabel('lzh') ?? null)
            : null;
    }
    public function getQIDAttribute() {
        return $this->wikidata_id ? 'Q' . $this->wikidata_id : null;
    }

    public function wikidata() {
        return $this->hasOne(Wikidata::class, 'id', 'wikidata_id');
    }


    // public function getSearchResult(): SearchResult {
    //     $author = $this->author;
    //     if($author) {
    //         $url = route('author/show', ['fakeId' => $author->fakeId]);
    //
    //         return new SearchResult(
    //             $author,
    //             $this->name,
    //             $url
    //         );
    //     }
    //
    //     if(!$this->wikidata) {
    //         // 由于删除author时，会删除相关alias，所以$this->author和$this->wikidata必有一个不为空
    //         // 此处返回 SearchResult 仅从逻辑完备考虑，正常情况下不会执行
    //         return new SearchResult($this, $this->name, '');
    //     }
    //
    //     $wikiData = $this->wikidata;
    //     $url = route('author/create-from-wikidata', ['wikidata_id' => $wikiData->id]);
    //     return new SearchResult(
    //         $wikiData,
    //         $this->name,
    //         $url
    //     );
    // }
}
