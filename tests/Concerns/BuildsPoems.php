<?php

namespace Tests\Concerns;

use App\Models\Language;
use App\Models\Poem;
use App\User;

trait BuildsPoems {
    protected function getOrCreateValidLanguageId(): int {
        $validLanguageIds = \App\Repositories\LanguageRepository::idsInUse();
        if ($validLanguageIds->isNotEmpty()) {
            return (int) $validLanguageIds->first();
        }

        $language = factory(Language::class)->create([
            'name'    => 'Test Language',
            'name_cn' => '测试语言',
        ]);

        return $language->id;
    }

    protected function makePoem(User $user, array $overrides = []): Poem {
        $suffix = uniqid();

        return Poem::create(array_merge([
            'title'             => '测试标题 ' . $suffix,
            'poet'              => '测试作者',
            'poem'              => "测试第一行 {$suffix}\n测试第二行 {$suffix}",
            'language_id'       => $this->getOrCreateValidLanguageId(),
            'original_id'       => 0,
            'is_owner_uploaded' => Poem::$OWNER['none'],
            'upload_user_id'    => $user->id,
            'flag'              => Poem::$FLAG['none'],
        ], $overrides));
    }
}