<?php

namespace App\Repositories;

use App\Models\Alias;
use App\Models\Author;
use App\Models\MediaFile;
use App\Models\Wikidata;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

/**
 * Class LanguageRepository.
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
     * @param string   $name
     * @param array    $authorIds
     * @param int|null $excludeAuthorId author_id that should be ignored
     * @return Collection
     */
    private static function _searchAlias(string $name, $authorIds = [], array $excludeAuthorId = null): Collection {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        $query = Alias::selectRaw('wikidata_id, min(wikidata_id) as id, min(name) as name, author_id')
            ->whereRaw("`name` LIKE $value");

        if (is_array($authorIds) && !empty($authorIds)) {
            $query->whereIn('author_id', $authorIds);
        }

        if (is_null($authorIds)) {
            $query->whereNull('author_id');
        }

        if (is_array($excludeAuthorId)) {
            $query->where(function ($q) use ($excludeAuthorId) {
                $q->whereNotIn('author_id', $excludeAuthorId)->orWhereNull('author_id');
            });
            // NOT(nullable fields <=> sth) 等价于以下条件：
            // $query->whereRaw("(`author_id` <> $excludeAuthorId or `author_id` is NULL)");
            // 必须添加 or `author_id` is NULL 条件，否则查询不到 author_id 为 NULL 的数据。
        }

        // TODO author should existed. has('author') here?
        $res = $query->groupBy(['wikidata_id', 'author_id'])->orderBy('author_id', 'desc')
            ->limit(self::SEARCH_LIMIT)->get()
            ->map->only(['QID', 'label_en', 'label_cn', 'label', 'url', 'author_id', 'wikidata_id'])->map(function ($item) {
                // don't replace this with select concat('Q', wikidata_id) as id, because it will be casted into integer
                $item['id'] = $item['author_id'] ?? $item['QID'];
                $item['source'] = $item['author_id'] ? 'PoemWiki' : 'Wikidata';

                $author = Author::find($item['author_id']);
                $wikidata = Wikidata::find($item['wikidata_id']);

                $item['avatar_url'] = $item['author_id'] && $author
                    ? $author->avatarUrl
                    : ($wikidata ? $wikidata->first_pic_url : config('app.avatar.default'));

                $item['desc'] = $item['author_id'] && $author
                    ? $author->describe_lang
                    : ($wikidata ? $wikidata->getDescription(config('app.locale')) : '');

                return $item;
            });

        return $res;
    }

    /**
     * Return searchable fields.
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

    public static function searchByName($name, $id = null) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        $query = Author::select(['id', 'name_lang'])
            ->whereRaw("JSON_SEARCH(lower(`name_lang`), 'all', $value)");

        if (is_numeric($id)) {
            $query->union(Author::find($id)->select(['id', 'name_lang']));
        }

        return $query->get()->toArray();
    }

    public static function searchByAlias($name, $authorId = null) {
        $res = self::_searchAlias($name, [$authorId]);

        return $res->toArray();
    }

    /**
     * @param string     $name
     * @param int[]|null $authorId
     * @return Collection
     */
    public static function searchLabel(string $name, ?array $authorId): Collection {
        $authorIds = collect($authorId)->toArray();

        $newAuthors = [];
        foreach ($authorIds as $id) {
            if (Str::startsWith($id, 'new_')) {
                $label        = substr($id, 4);
                $newAuthors[] = [
                    'id'         => $id,
                    'label'      => $label,
                    'label_cn'   => $label,
                    'label_en'   => $label,
                    'url'        => '',
                    'source'     => '',
                    'avatar_url' => '/images/avatar-default.png'
                ];
            }
        }
        if (count($authorIds)) {
            $resById = Author::select(['id', 'name_lang', 'pic_url', 'describe_lang'])->whereIn('id', $authorIds)->get()
                ->map->only(['id', 'label_en', 'label_cn', 'label', 'url', 'pic_url', 'describe_lang', 'avatar_url'])->map(function ($item) {
                    $item['source'] = 'PoemWiki';
                    $item['desc'] = $item['describe_lang'];

                    return $item;
                })->concat($newAuthors);
        }

        // TODO exclude wikidata_ids appeared in $resById
        $aliasRes = self::_searchAlias($name, [], $authorId);

        if (isset($resById)) {
            $aliasRes = $resById->concat($aliasRes);
        }

        return $aliasRes;
    }

    /**
     * Configure the Model.
     **/
    public static function model() {
        return Author::class;
    }

    /**
     * TODO this process should be a command.
     * @param Wikidata $wiki
     * @param int|null $user_id
     * @return AuthorRepository|\Illuminate\Database\Eloquent\Model
     */
    public function importFromWikidata(Wikidata $wiki, int $userID = null) {
        $entity = json_decode($wiki->data);

        $authorNameLang = [];
        foreach ($entity->labels as $locale => $label) {
            $authorNameLang[$locale] = $label->value;
        }

        $descriptionLang = [];
        $titleLocale     = $wiki->getSiteTitle(config('app.locale-wikipedia'));
        if (!empty($titleLocale)) {
            $summary = get_wikipedia_summary($titleLocale);

            if ($summary) {
                $descriptionLang[$titleLocale['locale']] = $summary;
            } else {
                foreach ($entity->descriptions as $locale => $description) {
                    $descriptionLang[$locale] = $description->value;
                }
            }
        }

        $picUrl = [];
        if (isset($entity->claims->P18)) {
            $P18 = $entity->claims->P18;
            foreach ($P18 as $image) {
                if (!isset($image->mainsnak->datavalue->value)) {
                    continue;
                }
                $fileName = str_replace(' ', '_', $image->mainsnak->datavalue->value);
                $ab       = substr(md5($fileName), 0, 2);
                $a        = substr($ab, 0, 1);
                $picUrl[] = Wikidata::$PIC_URL_BASE . $a . '/' . $ab . '/' . $fileName;
            }
        }

        // insert or update poet detail data into author
        $insert = [
            'name_lang'      => $authorNameLang,         // Don't json_encode translatable attributes
            'pic_url'        => $picUrl,                   // And Don't json_encode attributes that casted to json
            'wikidata_id'    => $wiki->id,
            'wikipedia_url'  => json_encode($entity->sitelinks),
            'describe_lang'  => $descriptionLang,    // Don't json_encode translatable attributes
            'upload_user_id' => $userID,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        return $this->updateOrCreate(['wikidata_id' => $wiki->id], $insert);
    }

    /**
     * Get a existed Author by wikidata_id
     * if not existed, create one from wikidata.
     * @param $wikidata_id int wikidata_id
     */
    public function getExistedAuthor($wikidata_id): Author {
        $authorExisted = Author::where('wikidata_id', '=', $wikidata_id)->first();

        if (!$authorExisted) {
            $wiki          = Wikidata::find($wikidata_id);
            $authorExisted = $this->importFromWikidata($wiki, Auth::user()->id);
            // Does this necessary?
            // 以下一步对于在前端查询前已导入过 wikidata label&alias 的 author 来说是不必要的，
            // 为适应未来在前端直接从 wikidata 接口查询未知 author 的数据，有 $poet_wikidata_id
            // 而暂未导入 wikidata label&alias 至 Alias 表的情况，保留以下导入过程
            $authorExisted->fetchWikiDesc();
            Artisan::call('alias:import', ['--id' => $wikidata_id]);
        }

        return $authorExisted;
    }

    public function saveAuthorMediaFile(Author $author, string $type, string $path, string $name, string $toFormat, int $size, int $fid = 0): MediaFile {
        $mediaFile = MediaFile::updateOrCreate([
            'model_type'     => Author::class,
            'model_id'       => $author->id,
            'type'           => $type,
            'path'           => $path,
        ], [
            'model_type'     => Author::class,
            'model_id'       => $author->id,
            'path'           => $path,
            'name'           => $name,
            'type'           => $type,
            'mime_type'      => GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS[$toFormat],
            'disk'           => 'cosv5',
            'size'           => $size,
            'fid'            => $fid
        ]);

        switch ($type) {
            case MediaFile::TYPE['image']:
                $author->relateToImage($mediaFile->id);

                break;

            case MediaFile::TYPE['avatar']:
                $author->relateToAvatar($mediaFile->id);

                break;
        }

        /* @var MediaFile $mediaFile */
        return $mediaFile;
    }
}
