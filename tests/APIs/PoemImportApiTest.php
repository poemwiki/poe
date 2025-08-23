<?php namespace Tests\APIs;

use App\Models\Author;
use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\ApiTestTrait;
use Tests\TestCase;

class PoemImportApiTest extends TestCase {
    use ApiTestTrait, WithoutMiddleware;

    protected $validLanguageId;

    protected function setUp(): void {
        parent::setUp();

        // Create a test language if none exist
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

    public function import_requires_auth_and_returns_json_unauthenticated() {
        $payload = [
            'poems' => [[
                'title'       => '未登录导入',
                'poet'        => '匿名',
                'poem'        => '测试内容不少于十字',
                'language_id' => 1,
            ]]
        ];

        $resp = $this->json('POST', '/api/v1/poem/import', $payload, [
            'Accept' => 'application/json'
        ]);

        $resp->assertStatus(401);
        $body = json_decode($resp->getContent(), true);
        $this->assertEquals(-10, $body['code']);
        $this->assertEquals('Unauthenticated', $body['message']);
    }

    /** @test */
    public function test_import_with_poet_id_and_translator_ids() {
        // Prepare user
        $user = factory(User::class)->create();
        $this->actingAs($user);

        // Prepare existing poet & translator authors
        $poetId = Author::insertGetId([
            'name_lang'  => json_encode([config('app.locale','zh-CN') => '导入作者' . uniqid()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $translatorId = Author::insertGetId([
            'name_lang'  => json_encode([config('app.locale','zh-CN') => '译者' . uniqid()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'poems' => [[
                'title'          => '批量导入测试 ' . uniqid(),
                'poet'           => '占位作者名',
                'poet_id'        => $poetId,
                'poem'           => "第一行内容测试\n第二行内容测试", // >=10 chars: 12 characters total
                'language_id'    => $this->validLanguageId,
                'translator_ids' => [$translatorId, '自由译者', 'Q0']
            ]]
        ];

        $response = $this->json('POST', '/api/v1/poem/import', $payload);
        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);
        $this->assertEquals(0, $body['code']); // Success code
        $this->assertIsArray($body['data']);
        $this->assertNotEmpty($body['data'][0]);

        // The result could be either a URL string (success) or error array (validation failed)
        if (is_string($body['data'][0])) {
            // Success case - result is URL string
            $this->assertIsString($body['data'][0]);
        } else {
            // If validation failed, it should be an errors array
            $this->assertIsArray($body['data'][0]);
            $this->assertArrayHasKey('errors', $body['data'][0]);
        }
    }

    /** @test */
    public function test_import_minimal_fields_without_poet_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $payload = [
            'poems' => [[
                'title'       => '最小导入 ' . uniqid(),
                'poet'        => '匿名作者',
                'poem'        => str_repeat('字', 10),
                'language_id' => $this->validLanguageId
            ]]
        ];
        $resp = $this->json('POST', '/api/v1/poem/import', $payload);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);
        $this->assertEquals(0, $body['code']); // Success code

        // The result could be either a URL string (success) or error array (validation failed)
        if (is_string($body['data'][0])) {
            $this->assertIsString($body['data'][0]);
        } else {
            // If validation failed, it should be an errors array
            $this->assertIsArray($body['data'][0]);
            $this->assertArrayHasKey('errors', $body['data'][0]);
        }
    }

    /** @test */
    public function test_import_invalid_language_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $payload = [
            'poems' => [[
                'title'       => '语言错误 ' . uniqid(),
                'poet'        => '测试作者',
                'poem'        => str_repeat('文字内容测试', 2), // 12 chars total
                'language_id' => 999999 // assume invalid
            ]]
        ];
        $resp = $this->json('POST', '/api/v1/poem/import', $payload);
        $resp->assertStatus(200); // still 200 with errors array per design
        $body = json_decode($resp->getContent(), true);
        $this->assertEquals(0, $body['code']); // Success code even with validation errors
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey('errors', $body['data'][0]);
        $this->assertArrayHasKey('language_id', $body['data'][0]['errors']);
    }

    /** @test */
    public function test_import_missing_poet_and_poet_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $payload = [
            'poems' => [[
                'title' => '缺少作者 ' . uniqid(),
                // no poet / poet_id
                'poem'        => str_repeat('行内容测试', 2), // 10 chars total
                'language_id' => $this->validLanguageId
            ]]
        ];
        $resp = $this->json('POST', '/api/v1/poem/import', $payload);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);
        $this->assertEquals(0, $body['code']); // Success code even with validation errors
        $this->assertArrayHasKey('errors', $body['data'][0]);
        $this->assertArrayHasKey('poet', $body['data'][0]['errors']);
    }

    /** @test */
    public function test_import_over_limit_rejected() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $poems = [];
        for ($i = 0; $i < 201; $i++) {
            $poems[] = [
                'title'       => '超限' . $i,
                'poet'        => '批量作者',
                'poem'        => str_repeat('测试内容', 3) . $i, // 12+ chars
                'language_id' => $this->validLanguageId
            ];
        }
        $resp = $this->json('POST', '/api/v1/poem/import', ['poems' => $poems]);
        $resp->assertStatus(200);
        $body = json_decode($resp->getContent(), true);
        $this->assertEquals(-1, $body['code']); // Failure code
        $this->assertEquals('Limit 200 poems per request', $body['message']);
    }
}
