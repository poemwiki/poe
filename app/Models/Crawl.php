<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
