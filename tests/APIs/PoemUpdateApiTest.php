<?php namespace Tests\APIs;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Poem;
use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\ApiTestTrait;
use Tests\TestCase;

class PoemUpdateApiTest extends TestCase {
    // `WithoutMiddleware` only disables HTTP middleware; FormRequest::authorize()
    // still runs, so these tests still exercise the request authorization paths.
    use ApiTestTrait, WithoutMiddleware;

    protected $validLanguageId;

    protected function setUp(): void {
        parent::setUp();

        $validLanguageIds = \App\Repositories\LanguageRepository::idsInUse();
        if ($validLanguageIds->isEmpty()) {
            $language = factory(\App\Models\Language::class)->create([
                'name'    => 'Test Language',
                'name_cn' => '测试语言',
            ]);
            $this->validLanguageId = $language->id;
        } else {
            $this->validLanguageId = $validLanguageIds->first();
        }
    }

    /** @test */
    public function test_update_poem_returns_id_fake_id_and_url() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $poem = Poem::create([
            'title'             => '更新前标题 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "更新前第一行\n更新前第二行",
            'language_id'       => $this->validLanguageId,
            'original_id'       => 0,
            'is_owner_uploaded' => Poem::$OWNER['none'],
            'upload_user_id'    => $user->id,
            'flag'              => Poem::$FLAG['none'],
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
    public function test_update_poem_original_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $originalPoem = Poem::create([
            'title'             => '原作 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "原作第一行\n原作第二行",
            'language_id'       => $this->validLanguageId,
            'original_id'       => 0,
            'is_owner_uploaded' => Poem::$OWNER['none'],
            'upload_user_id'    => $user->id,
            'flag'              => Poem::$FLAG['none'],
        ]);
        $translatedPoem = Poem::create([
            'title'             => '待更新译作 ' . uniqid(),
            'poet'              => '译者',
            'poem'              => "译作第一行\n译作第二行",
            'language_id'       => $this->validLanguageId,
            'original_id'       => 0,
            'is_owner_uploaded' => Poem::$OWNER['none'],
            'upload_user_id'    => $user->id,
            'flag'              => Poem::$FLAG['none'],
        ]);

        $resp = $this->json('POST', '/api/v1/poem/update/' . $translatedPoem->id, [
            'original_id' => $originalPoem->id,
            'is_original' => false,
        ]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);

        $this->assertEquals(0, $body['code']);
        $this->assertEquals($translatedPoem->id, $body['data']['id']);

        $translatedPoem->refresh();
        $this->assertEquals($originalPoem->id, $translatedPoem->original_id);
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

        $poem = Poem::create([
            'title'             => '清空译者测试 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "清空译者第一行\n清空译者第二行",
            'translator'        => '旧译者文本',
            'language_id'       => $this->validLanguageId,
            'original_id'       => 0,
            'is_owner_uploaded' => Poem::$OWNER['none'],
            'upload_user_id'    => $user->id,
            'flag'              => Poem::$FLAG['none'],
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
    public function test_update_poem_by_fake_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $poem = Poem::create([
            'title'             => 'fakeId 更新前标题 ' . uniqid(),
            'poet'              => '原作者',
            'poem'              => "fakeId 更新前第一行\nfakeId 更新前第二行",
            'language_id'       => $this->validLanguageId,
            'original_id'       => 0,
            'is_owner_uploaded' => Poem::$OWNER['none'],
            'upload_user_id'    => $user->id,
            'flag'              => Poem::$FLAG['none'],
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

        $poem = Poem::create([
            'title'             => '被拒绝更新 ' . uniqid(),
            'poet'              => '原创作者',
            'poem'              => "原创第一行\n原创第二行",
            'language_id'       => $this->validLanguageId,
            'original_id'       => 0,
            'is_owner_uploaded' => Poem::$OWNER['uploader'],
            'upload_user_id'    => $owner->id,
            'flag'              => Poem::$FLAG['none'],
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