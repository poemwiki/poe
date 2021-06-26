<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Crawl
 *
 * @package App\Models
 * @property int $id
 * @property int $f_crawl_id
 * @property int $admin_user_id
 * @property string $url
 * @property string $model
 * @property string $name
 * @property mixed|null $export_setting
 * @property int|null $exported_id
 * @property mixed|null $result
 * @property string|null $html
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read mixed $exported_entry
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl query()
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereAdminUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereExportSetting($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereExportedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereFCrawlId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Crawl whereUrl($value)
 * @mixin \Eloquent
 */
class Crawl extends Model {
    protected $table = 'crawl';

    protected $fillable = [
        'url',
        'model',
        'name',
        'html',
        'result',
        'export_setting',
        'exported_id',
        'f_crawl_id',
        'admin_user_id'
    ];


    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public $casts = [
       // 'result' => 'json',
       // 'export_setting' => 'json'
    ];

    protected $appends = [];

    public function getExportedEntryAttribute() {
        $modelName = $this->export_setting['model'];
        /** @var Author|Poem $model */
        $model = new $$modelName();
        return $model->find($this->exported['id']);
    }
}
