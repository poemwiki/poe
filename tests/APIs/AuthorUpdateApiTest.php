<?php namespace Tests\APIs;

use App\Models\Author;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestTrait;
use Tests\Concerns\CreatesAuthorLookups;
use Tests\TestCase;

class AuthorUpdateApiTest extends TestCase {
    use ApiTestTrait, CreatesAuthorLookups, WithoutMiddleware;

    /** @test */
    public function test_update_author_desc_only_keeps_name() {
        $authorName = '原始作者名 ' . uniqid();
        $authorDesc = '原始简介 ' . uniqid();
        $newDesc    = '更新后的简介 ' . uniqid();
        $authorId   = DB::table('author')->insertGetId([
            'name_lang'     => json_encode([config('app.locale', 'zh-CN') => $authorName], JSON_UNESCAPED_UNICODE),
            'describe_lang' => json_encode([config('app.locale', 'zh-CN') => $authorDesc], JSON_UNESCAPED_UNICODE),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $response = $this->json('POST', '/api/v1/author/update/' . $authorId, [
            'desc' => [config('app.locale', 'zh-CN') => $newDesc],
        ]);

        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);

        $this->assertEquals(0, $body['code']);
        $this->assertEquals($authorId, $body['data']['id']);

        $storedAuthor = DB::table('author')->where('id', $authorId)->first();
        $this->assertSame($authorName, json_decode($storedAuthor->name_lang, true)[config('app.locale', 'zh-CN')] ?? null);
        $this->assertSame($newDesc, json_decode($storedAuthor->describe_lang, true)[config('app.locale', 'zh-CN')] ?? null);
    }

    /** @test */
    public function test_update_author_supports_dynasty_id_and_nation_id() {
        $authorName = '更新作者 ' . uniqid();
        $authorDesc = '原始简介 ' . uniqid();
        $authorId   = DB::table('author')->insertGetId([
            'name_lang'     => json_encode([config('app.locale', 'zh-CN') => $authorName], JSON_UNESCAPED_UNICODE),
            'describe_lang' => json_encode([config('app.locale', 'zh-CN') => $authorDesc], JSON_UNESCAPED_UNICODE),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $dynastyId = $this->createDynasty()->id;
        $nationId  = $this->createNation()->id;

        $response = $this->json('POST', '/api/v1/author/update/' . $authorId, [
            'dynasty_id' => $dynastyId,
            'nation_id'  => $nationId,
        ]);

        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);

        $this->assertEquals(0, $body['code']);
        $this->assertEquals($authorId, $body['data']['id']);

        $author = Author::find($authorId);
        $this->assertNotNull($author);
        $this->assertSame($dynastyId, $author->dynasty_id);
        $this->assertSame($nationId, $author->nation_id);

        $storedAuthor = DB::table('author')->where('id', $authorId)->first();
        $this->assertSame($authorName, json_decode($storedAuthor->name_lang, true)[config('app.locale', 'zh-CN')] ?? null);
        $this->assertSame($authorDesc, json_decode($storedAuthor->describe_lang, true)[config('app.locale', 'zh-CN')] ?? null);
    }

    /** @test */
    public function test_update_author_rejects_soft_deleted_relation_without_changing_author() {
        $dynasty = $this->createDynasty();
        $nation  = $this->createNation();
        $author  = Author::forceCreate([
            'name_lang'  => [config('app.locale', 'zh-CN') => '保持不变的作者'],
            'dynasty_id' => $dynasty->id,
            'nation_id'  => $nation->id,
        ]);
        $deletedNation = $this->createNation();
        $deletedNation->delete();

        $response = $this->json('POST', '/api/v1/author/update/' . $author->id, [
            'nation_id' => $deletedNation->id,
        ]);

        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);

        $this->assertSame(422, $body['code']);
        $this->assertArrayHasKey('nation_id', $body['data']);
        $author->refresh();
        $this->assertSame($dynasty->id, $author->dynasty_id);
        $this->assertSame($nation->id, $author->nation_id);
    }

    /** @test */
    public function test_update_author_can_clear_relation_with_explicit_null() {
        $dynasty = $this->createDynasty();
        $nation  = $this->createNation();
        $author  = Author::forceCreate([
            'name_lang'  => [config('app.locale', 'zh-CN') => '清空朝代的作者'],
            'dynasty_id' => $dynasty->id,
            'nation_id'  => $nation->id,
        ]);

        $response = $this->json('POST', '/api/v1/author/update/' . $author->id, [
            'dynasty_id' => null,
        ]);

        $response->assertStatus(200);
        $this->assertSame(0, json_decode($response->getContent(), true)['code']);
        $author->refresh();
        $this->assertNull($author->dynasty_id);
        $this->assertSame($nation->id, $author->nation_id);
    }
}
