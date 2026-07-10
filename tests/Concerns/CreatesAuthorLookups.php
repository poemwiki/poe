<?php

namespace Tests\Concerns;

use App\Models\Dynasty;
use App\Models\Nation;

trait CreatesAuthorLookups {
    protected function createDynasty(): Dynasty {
        $name = '测试朝代 ' . uniqid();

        return Dynasty::forceCreate([
            'name'      => $name,
            'name_lang' => [config('app.locale', 'zh-CN') => $name],
            'f_id'      => 0,
        ]);
    }

    protected function createNation(): Nation {
        $name = '测试国家 ' . uniqid();

        return Nation::forceCreate([
            'name_lang' => [config('app.locale', 'zh-CN') => $name],
            'f_id'      => 0,
        ]);
    }
}
