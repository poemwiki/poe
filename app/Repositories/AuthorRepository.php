<?php

namespace App\Repositories;

use App\Models\Alias;
use App\Models\Author;
use App\Models\Wikidata;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository
 * @package App\Repositories
 * @version July 19, 2020, 11:24 am UTC
 */
class AuthorRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name_lang',
        'id'
    ];

    /**
     * @param string $name
     * @param array|null $authorId
     * @param int|null $excludeAuthorId author_id that should be ignored
     * @return Collection
     */
    private static function _searchAlias(string $name, $authorIds=[], $excludeAuthorId=null): Collection {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        $query = Alias::selectRaw('wikidata_id, min(wikidata_id) as id, min(name) as name, author_id')
            ->whereRaw("lower(`name`) LIKE $value");

        if (is_array($authorIds) && !empty($authorIds))
            $query->whereIn('author_id', $authorIds);

        if (is_null($authorIds))
            $query->whereNull('author_id');

        if (is_numeric($excludeAuthorId)) {
            $query->whereRaw("NOT(`author_id` <=> $excludeAuthorId)");
            // NOT(nullable fields <=> sth) 等价于以下条件：
            // $query->whereRaw("(`author_id` <> $excludeAuthorId or `author_id` is NULL)");
            // 必须添加 or `author_id` is NULL 条件，否则查询不到 author_id 为 NULL 的数据。
        }

        // TODO author should existed. has('author') here?
        $res = $query->groupBy(['wikidata_id', 'author_id'])->orderBy('author_id', 'desc')
            ->limit(self::SEARCH_LIMIT)->get()
            ->map->only(['QID', 'label_en', 'label_cn', 'label', 'url', 'author_id', 'wikidata_id'])->map(function ($item) {
                $item['id'] = $item['author_id'] ?? $item['QID']; // don't replace this with select concat('Q', wikidata_id) as id, because it will be casted into integer
                $item['source'] = $item['author_id'] ? 'PoemWiki' : 'Wikidata';
                $item['avatar_url'] = $item['author_id'] ? Author::find($item['author_id'])->avatarUrl : Wikidata::find($item['wikidata_id'])->first_pic_url;
                $item['desc'] = $item['author_id'] ? Author::find($item['author_id'])->describe_lang : Wikidata::find(str_replace('Q', '', $item['QID']))->getDescription(config('app.locale'));
                return $item;
            });
        return $res;
    }

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable() {
        return $this->fieldSearchable;
    }

    public static function findByName($name) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        return Author::select(['id', 'name_lang'])
            ->whereRaw('LOWER(author.name_lang) LIKE ' . $value)->first()->toArray();
    }

    public static function searchByName($name, $id=null) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        $query = Author::select(['id', 'name_lang'])
            ->whereRaw("JSON_SEARCH(lower(`name_lang`), 'all', $value)");

        if(is_numeric($id))
            $query->union(Author::find($id)->select(['id', 'name_lang']));

        return $query->get()->toArray();
    }

    public static function searchByAlias($name, $authorId=null) {
        $res = self::_searchAlias($name, [$authorId]);

        return $res->toArray();
    }

    public static function searchLabel($name, $authorId=null) {
        if(is_numeric($authorId)) {
            $resById = Author::select(['id', 'name_lang', 'pic_url', 'describe_lang'])->where('id', '=', $authorId)->get()
                ->map->only(['id', 'label_en', 'label_cn', 'label', 'url', 'pic_url', 'describe_lang', 'avatar_url'])->map(function ($item) {
                    $item['source'] = 'PoemWiki';
                    $item['desc'] = $item['describe_lang'];
                    // $item['avatar_url'] = $item['avatar_url'];
                    // dd($item);
                    return $item;
                });
        }

        $aliasRes = self::_searchAlias($name, [], $authorId);

        if(isset($resById))
            $aliasRes = $resById->concat($aliasRes);

        return $aliasRes->toArray();
    }

    /**
     * Configure the Model
     **/
    public static function model() {
        return Author::class;
    }


    /**
     * TODO this process should be a command
     * @param Wikidata $wiki
     * @return Author
     */
    public function importFromWikidata(Wikidata $wiki) {
        $entity = json_decode($wiki->data);

        $authorNameLang = [];
        foreach ($entity->labels as $locale => $label) {
            $authorNameLang[$locale] = $label->value;
        }
        $descriptionLang = [];
        foreach ($entity->descriptions as $locale => $description) {
            $descriptionLang[$locale] = $description->value;
        }

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
                $picUrl[] = Wikidata::$PIC_URL_BASE . $a . '/' . $ab . '/' . $fileName;
            }
        }

        // insert or update poet detail data into author
        $insert = [
            'name_lang' => $authorNameLang,         // Don't json_encode translatable attributes
            'pic_url' => $picUrl,                   // And Don't json_encode attributes that casted to json
            'wikidata_id' => $wiki->id,
            'wikipedia_url' => json_encode($entity->sitelinks),
            'describe_lang' => $descriptionLang,    // Don't json_encode translatable attributes
            "created_at" => now(),
            "updated_at" => now(),
        ];
        return $this->updateOrCreate(['wikidata_id' => $wiki->id], $insert);
    }


    /**
     * Get a existed Author by wikidata_id
     * @param $wikidata_id
     */
    public function getExistedAuthor($wikidata_id) : Author {
        $authorExisted = Author::where('wikidata_id', '=', $wikidata_id)->first();

        if (!$authorExisted) {
            $wiki = Wikidata::find($wikidata_id);
            $authorExisted = $this->importFromWikidata($wiki);
            // Does this necessary?
            // 以下一步对于在前端查询前已导入过 wikidata label&alias 的 author 来说是不必要的，
            // 为适应未来在前端直接从 wikidata 接口查询未知 author 的数据，有 $poet_wikidata_id
            // 而暂未导入 wikidata label&alias 至 Alias 表的情况，保留以下导入过程
            $authorExisted->fetchWikiDesc();
            Artisan::call('alias:import', ['--id' => $wikidata_id]);
        }
        return $authorExisted;
    }
}
