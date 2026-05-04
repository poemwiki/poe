<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Poem;
use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\Concerns\BuildsPoems;
use Tests\TestCase;

class PoemWebUpdateTest extends TestCase {
    use BuildsPoems, WithoutMiddleware;

    protected $validLanguageId;

    protected function setUp(): void {
        parent::setUp();

        $this->validLanguageId = $this->getOrCreateValidLanguageId();
    }

    /** @test */
    public function test_web_update_changes_original_poem_to_translated_poem_sets_expected_original_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $poetId         = $this->makeAuthorId('诗人');
        $linkedOriginal = $this->makePoem($user, [
            'title'   => 'web 原作链接目标 ' . uniqid(),
            'poet'    => '原作者',
            'poet_cn' => '原作者',
            'poet_id' => $poetId,
        ]);

        $poemWithoutOriginalLink = $this->makePoem($user, [
            'title'   => 'web 原作改译作无链接 ' . uniqid(),
            'poet'    => '原作者',
            'poet_cn' => '原作者',
            'poet_id' => $poetId,
        ]);
        $payload = $this->makeWebUpdatePayload($poemWithoutOriginalLink, [
            'is_original'   => 0,
            'original_link' => null,
        ]);
        unset($payload['original_id']);

        $this->assertWebUpdateSucceeded($poemWithoutOriginalLink, $payload);

        $poemWithoutOriginalLink->refresh();
        $this->assertSame(0, $poemWithoutOriginalLink->original_id);
        $this->assertSame(0, $poemWithoutOriginalLink->is_original);

        $poemWithOriginalLink = $this->makePoem($user, [
            'title'   => 'web 原作改译作有链接 ' . uniqid(),
            'poet'    => '原作者',
            'poet_cn' => '原作者',
            'poet_id' => $poetId,
        ]);
        $payload = $this->makeWebUpdatePayload($poemWithOriginalLink, [
            'is_original'   => 0,
            'original_link' => $linkedOriginal->url,
        ]);
        unset($payload['original_id']);

        $this->assertWebUpdateSucceeded($poemWithOriginalLink, $payload);

        $poemWithOriginalLink->refresh();
        $this->assertSame($linkedOriginal->id, $poemWithOriginalLink->original_id);
        $this->assertSame(0, $poemWithOriginalLink->is_original);
    }

    /** @test */
    public function test_web_update_changes_translated_poem_to_original_poem_sets_own_original_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $poetId       = $this->makeAuthorId('诗人');
        $originalPoem = $this->makePoem($user, [
            'title'   => 'web 已有关联原作 ' . uniqid(),
            'poet'    => '原作者',
            'poet_cn' => '原作者',
            'poet_id' => $poetId,
        ]);
        $otherOriginalPoem = $this->makePoem($user, [
            'title'   => 'web 另一首原作 ' . uniqid(),
            'poet'    => '原作者',
            'poet_cn' => '原作者',
            'poet_id' => $poetId,
        ]);

        $poemWithoutOriginalLink = $this->makePoem($user, [
            'title'       => 'web 译作改原作无链接 ' . uniqid(),
            'poet'        => '原作者',
            'poet_cn'     => '原作者',
            'poet_id'     => $poetId,
            'is_original' => 0,
            'original_id' => $originalPoem->id,
        ]);
        $payload = $this->makeWebUpdatePayload($poemWithoutOriginalLink, [
            'is_original'   => 1,
            'original_link' => null,
        ]);

        $this->assertWebUpdateSucceeded($poemWithoutOriginalLink, $payload);

        $poemWithoutOriginalLink->refresh();
        $this->assertSame($poemWithoutOriginalLink->id, $poemWithoutOriginalLink->original_id);
        $this->assertSame(1, $poemWithoutOriginalLink->is_original);

        $poemWithOriginalLink = $this->makePoem($user, [
            'title'       => 'web 译作改原作有链接 ' . uniqid(),
            'poet'        => '原作者',
            'poet_cn'     => '原作者',
            'poet_id'     => $poetId,
            'is_original' => 0,
            'original_id' => $originalPoem->id,
        ]);
        $payload = $this->makeWebUpdatePayload($poemWithOriginalLink, [
            'is_original'   => 1,
            'original_id'   => $otherOriginalPoem->id,
            'original_link' => $otherOriginalPoem->url,
        ]);

        $this->assertWebUpdateSucceeded($poemWithOriginalLink, $payload);

        $poemWithOriginalLink->refresh();
        $this->assertSame($poemWithOriginalLink->id, $poemWithOriginalLink->original_id);
        $this->assertSame(1, $poemWithOriginalLink->is_original);
    }

    private function makeAuthorId(string $prefix): int {
        return Author::insertGetId([
            'name_lang'  => json_encode([config('app.locale', 'zh-CN') => $prefix . ' ' . uniqid()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeWebUpdatePayload(Poem $poem, array $overrides = []): array {
        return array_merge([
            'title'                  => $poem->title,
            'language_id'            => $poem->language_id ?: $this->validLanguageId,
            'is_original'            => $poem->is_original,
            'original_id'            => $poem->original_id,
            'poet'                   => $poem->poet,
            'poet_cn'                => $poem->poet_cn,
            'bedtime_post_id'        => $poem->bedtime_post_id,
            'bedtime_post_title'     => $poem->bedtime_post_title,
            'poem'                   => $poem->poem,
            'translator'             => $poem->translator,
            'from'                   => $poem->from,
            'year'                   => $poem->year,
            'month'                  => $poem->month,
            'date'                   => $poem->date,
            'preface'                => $poem->preface,
            'subtitle'               => $poem->subtitle,
            'genre_id'               => $poem->genre_id,
            'poet_id'                => $poem->poet_id,
            'translator_id'          => $poem->translator_id,
            'location'               => $poem->location,
            'poet_wikidata_id'       => $poem->poet_wikidata_id,
            'translator_wikidata_id' => $poem->translator_wikidata_id,
            'original_link'          => $poem->originalLink,
            'translator_ids'         => [],
        ], $overrides);
    }

    private function assertWebUpdateSucceeded(Poem $poem, array $payload): void {
        $resp = $this->json('POST', '/poems/update/' . $poem->fakeId, $payload, [
            'Accept' => 'application/json',
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);
    }
}