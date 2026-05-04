<?php namespace Tests\APIs;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\ApiTestTrait;
use Tests\Concerns\BuildsPoems;
use Tests\TestCase;

class PoemUpdateApiTest extends TestCase {
    // `WithoutMiddleware` only disables HTTP middleware; FormRequest::authorize()
    // still runs, so these tests still exercise the request authorization paths.
    use ApiTestTrait, BuildsPoems, WithoutMiddleware;

    protected $validLanguageId;

    protected function setUp(): void {
        parent::setUp();

        $this->validLanguageId = $this->getOrCreateValidLanguageId();
    }

    /** @test */
    public function test_update_poem_returns_id_fake_id_and_url() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $poem = $this->makePoem($user, [
            'title'             => '更新前标题 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "更新前第一行\n更新前第二行",
        ]);

        $payload = [
            'title' => '更新后标题 ' . uniqid(),
            'from'  => 'API update test source',
        ];

        $resp = $this->json('POST', '/api/v1/poem/update/' . $poem->id, $payload);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);
        $this->assertEquals($poem->id, $body['data']['id']);
        $this->assertEquals($poem->fakeId, $body['data']['fakeId']);
        $this->assertEquals($poem->url, $body['data']['url']);

        $poem->refresh();
        $this->assertEquals($payload['title'], $poem->title);
        $this->assertEquals($payload['from'], $poem->from);
    }

    /** @test */
    public function test_update_poem_changes_original_poem_to_translated_poem_sets_expected_original_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $originalPoem = $this->makePoem($user, [
            'title' => '原作链接目标 ' . uniqid(),
            'poet'  => '原作者',
        ]);

        $poemWithoutOriginal = $this->makePoem($user, [
            'title' => '原作改译作无原作 ' . uniqid(),
            'poet'  => '原作者',
        ]);
        $resp = $this->json('POST', '/api/v1/poem/update/' . $poemWithoutOriginal->id, [
            'is_original' => false,
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);
        $this->assertEquals($poemWithoutOriginal->id, $body['data']['id']);

        $poemWithoutOriginal->refresh();
        $this->assertSame(0, $poemWithoutOriginal->original_id);
        $this->assertSame(0, $poemWithoutOriginal->is_original);

        $poemWithOriginal = $this->makePoem($user, [
            'title' => '原作改译作有原作 ' . uniqid(),
            'poet'  => '原作者',
        ]);
        $resp = $this->json('POST', '/api/v1/poem/update/' . $poemWithOriginal->id, [
            'original_id' => $originalPoem->id,
            'is_original' => false,
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);
        $this->assertEquals($poemWithOriginal->id, $body['data']['id']);

        $poemWithOriginal->refresh();
        $this->assertSame($originalPoem->id, $poemWithOriginal->original_id);
        $this->assertSame(0, $poemWithOriginal->is_original);
    }

    /** @test */
    public function test_update_poem_changes_translated_poem_to_original_poem_sets_own_original_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $originalPoem = $this->makePoem($user, [
            'title' => '原有原作 ' . uniqid(),
            'poet'  => '原作者',
        ]);
        $otherOriginalPoem = $this->makePoem($user, [
            'title' => '另一首原作 ' . uniqid(),
            'poet'  => '原作者',
        ]);

        $poemWithoutOriginalId = $this->makePoem($user, [
            'title'       => '译作改原作无 original_id ' . uniqid(),
            'poet'        => '译者',
            'is_original' => 0,
            'original_id' => $originalPoem->id,
        ]);
        $resp = $this->json('POST', '/api/v1/poem/update/' . $poemWithoutOriginalId->id, [
            'is_original' => true,
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);

        $poemWithoutOriginalId->refresh();
        $this->assertSame($poemWithoutOriginalId->id, $poemWithoutOriginalId->original_id);
        $this->assertSame(1, $poemWithoutOriginalId->is_original);

        $poemWithOriginalId = $this->makePoem($user, [
            'title'       => '译作改原作有 original_id ' . uniqid(),
            'poet'        => '译者',
            'is_original' => 0,
            'original_id' => $originalPoem->id,
        ]);
        $resp = $this->json('POST', '/api/v1/poem/update/' . $poemWithOriginalId->id, [
            'is_original' => true,
            'original_id' => $otherOriginalPoem->id,
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);

        $poemWithOriginalId->refresh();
        $this->assertSame($poemWithOriginalId->id, $poemWithOriginalId->original_id);
        $this->assertSame(1, $poemWithOriginalId->is_original);
    }

    /** @test */
    public function test_update_poem_poet_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $oldPoetId = Author::insertGetId([
            'name_lang'  => json_encode([config('app.locale', 'zh-CN') => '旧作者 ' . uniqid()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $newPoetId = Author::insertGetId([
            'name_lang'  => json_encode([config('app.locale', 'zh-CN') => '新作者 ' . uniqid()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $poem = $this->makePoem($user, [
            'title'             => '修改作者测试 ' . uniqid(),
            'poet'              => '旧作者文本',
            'poet_id'           => $oldPoetId,
            'poem'              => "修改作者第一行\n修改作者第二行",
        ]);

        $resp = $this->json('POST', '/api/v1/poem/update/' . $poem->id, [
            'poet_id' => $newPoetId,
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);

        $poem->refresh();
        $this->assertSame($newPoetId, $poem->poet_id);
    }

    /** @test */
    public function test_update_poem_content() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $newPoemContent = '更新后的正文第一行 ' . uniqid() . "\n更新后的正文第二行 " . uniqid();

        $poem = $this->makePoem($user, [
            'title'             => '修改正文测试 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "修改前正文第一行\n修改前正文第二行",
        ]);

        $resp = $this->json('POST', '/api/v1/poem/update/' . $poem->id, [
            'poem' => $newPoemContent,
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);

        $poem->refresh();
        $this->assertSame($newPoemContent, $poem->poem);
    }

    /** @test */
    public function test_update_poem_empty_translator_ids_do_not_clear_translators() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $translatorId = Author::insertGetId([
            'name_lang'  => json_encode([config('app.locale', 'zh-CN') => '待清空译者 ' . uniqid()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $poem = $this->makePoem($user, [
            'title'             => '清空译者测试 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "清空译者第一行\n清空译者第二行",
            'translator'        => '旧译者文本',
        ]);
        $poem->relateToTranslators([$translatorId]);

        $resp = $this->json('POST', '/api/v1/poem/update/' . $poem->id, [
            'translator_ids' => [],
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);
        $this->assertEquals($poem->id, $body['data']['id']);

        $poem->refresh();
        $this->assertSame(1, $poem->relatedTranslators()->count());
    }

    /** @test */
    public function test_update_poem_accepts_plain_text_translator_names() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $translatorName = '裸字符串译者 ' . uniqid();

        $poem = $this->makePoem($user, [
            'title'             => '裸字符串译者测试 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "裸字符串译者第一行\n裸字符串译者第二行",
        ]);

        $resp = $this->json('POST', '/api/v1/poem/update/' . $poem->id, [
            'translator_ids' => [$translatorName],
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);

        $poem->refresh();
        $this->assertSame(json_encode([$translatorName], JSON_UNESCAPED_UNICODE), $poem->translator);
        $this->assertSame($translatorName, $poem->translatorsStr);
        $this->assertSame(1, $poem->relatedTranslators()->count());
    }

    /** @test */
    public function test_update_poem_ignores_internal_only_fields() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $poem = $this->makePoem($user, [
            'title'                  => '内部字段忽略测试 ' . uniqid(),
            'poet'                   => '原作者',
            'poem'                   => "内部字段忽略第一行\n内部字段忽略第二行",
            'bedtime_post_id'        => 123,
            'bedtime_post_title'     => '旧 bedtime 标题',
            'need_confirm'           => 0,
            'poet_wikidata_id'       => 1001,
            'translator_wikidata_id' => 1002,
        ]);

        $resp = $this->json('POST', '/api/v1/poem/update/' . $poem->id, [
            'from'                   => 'allowed field',
            'bedtime_post_id'        => 999,
            'bedtime_post_title'     => '新 bedtime 标题',
            'need_confirm'           => 1,
            'poet_wikidata_id'       => 999998,
            'translator_wikidata_id' => 999999,
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);

        $poem->refresh();
        $this->assertSame('allowed field', $poem->from);
        $this->assertSame(123, $poem->bedtime_post_id);
        $this->assertSame('旧 bedtime 标题', $poem->bedtime_post_title);
        $this->assertSame(0, $poem->need_confirm);
        $this->assertSame(1001, $poem->poet_wikidata_id);
        $this->assertSame(1002, $poem->translator_wikidata_id);
    }

    /** @test */
    public function test_update_poem_by_fake_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $poem = $this->makePoem($user, [
            'title'             => 'fakeId 更新前标题 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "fakeId 更新前第一行\nfakeId 更新前第二行",
        ]);

        $resp = $this->json('POST', '/api/v1/poem/update/' . $poem->fakeId, [
            'from' => 'updated by fakeId',
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);
        $this->assertEquals($poem->id, $body['data']['id']);
        $this->assertEquals($poem->fakeId, $body['data']['fakeId']);

        $poem->refresh();
        $this->assertEquals('updated by fakeId', $poem->from);
    }

    /** @test */
    public function test_update_poem_not_found() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $resp = $this->json('POST', '/api/v1/poem/update/99999999', [
            'title' => '不存在的诗歌',
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(Controller::$CODE['not_found'], $body['code']);
        $this->assertEquals('Poem not found', $body['message']);
    }

    /** @test */
    public function test_update_poem_not_found_requires_authenticated_user_to_pass_authorize_branch() {
        $resp = $this->json('POST', '/api/v1/poem/update/99999999', [
            'title' => '未登录不存在的诗歌',
        ], [
            'Accept' => 'application/json'
        ]);

        $resp->assertStatus(403);
    }

    /** @test */
    public function test_update_poem_denied_by_gate_for_other_user_owned_original() {
        $owner     = factory(User::class)->create();
        $otherUser = factory(User::class)->create();
        $this->actingAs($otherUser);

        $poem = $this->makePoem($owner, [
            'title'             => '被拒绝更新 ' . uniqid(),
            'poet'              => '原创作者',
            'poem'              => "原创第一行\n原创第二行",
            'is_owner_uploaded' => \App\Models\Poem::$OWNER['uploader'],
        ]);

        $resp = $this->json('POST', '/api/v1/poem/update/' . $poem->id, [
            'title' => '试图越权修改',
        ], [
            'Accept' => 'application/json'
        ]);

        $resp->assertStatus(403);
        $poem->refresh();
        $this->assertNotEquals('试图越权修改', $poem->title);
    }
}