<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

/**
 * @property Wikidata wikidata
 */
class Alias extends Model implements Searchable {
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
        return $this->belongsTo(\App\Models\Author::class, 'author_id', 'id');
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
            ? ($this->wikidata->getLabel('zh') ?? $this->wikidata->getLabel('zh-hant') ?? $this->wikidata->getLabel('lzh') ?? null)
            : null;
    }
    public function getQIDAttribute() {
        return 'Q'.$this->id;
    }

    public function wikidata() {
        return $this->hasOne(\App\Models\Wikidata::class, 'id', 'wikidata_id');
    }


    public function getSearchResult(): SearchResult {
        $author = $this->author;
        if($author) {
            $url = route('author/show', ['fakeId' => $author->fakeId]);

            return new SearchResult(
                $author,
                $this->name,
                $url
            );
        }

        $wikiData = $this->wikidata;
        $url = route('author/create-from-wikidata', ['wikidata_id' => $wikiData->id]);
        return new SearchResult(
            $wikiData,
            $this->name,
            $url
        );
    }
}
