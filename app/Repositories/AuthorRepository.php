<?php

namespace App\Repositories;

use App\Models\Alias;
use App\Models\Author;
use App\Models\Wikidata;
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

    public static function searchByAlias($name, $id=null) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        $query = Alias::selectRaw('wikidata_id, min(wikidata_id) as id, min(name) as name')
            ->whereRaw("lower(`name`) LIKE $value");

        if(is_numeric($id))
            $query->where('author_id', '<>', $id);

        $res = $query->groupBy('wikidata_id')->limit(10)->get()
            ->map->only('QID', 'label_en', 'label_cn', 'label', 'url')->map(function ($item) {

                $item['id'] = $item['QID']; // don't replace this with select concat('Q', wikidata_id) as id, because it will be casted into integer
                return $item;
            });

        if(is_numeric($id)) {
            $res = Author::select(['id', 'name_lang'])->where('id', '=', $id)->get()
                ->map->only('id', 'label_en', 'label_cn', 'label', 'url')->concat($res);
        }
        // dd($res->toArray());
        return $res->toArray();
    }

    /**
     * Configure the Model
     **/
    public static function model() {
        return Author::class;
    }


    /**
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
                $picUrl[] = Wikidata::PIC_URL_BASE . $a . '/' . $ab . '/' . $fileName;
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
        $author = Author::updateOrCreate(['wikidata_id' => $wiki->id], $insert);

        return $author;
    }
}
