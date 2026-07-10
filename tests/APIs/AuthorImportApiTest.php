<?php namespace Tests\APIs;

use App\Models\Author;
use App\Models\Wikidata;
use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestTrait;
use Tests\Concerns\CreatesAuthorLookups;
use Tests\TestCase;

class AuthorImportApiTest extends TestCase {
    // Removed RefreshDatabase to avoid running migrations; using pre-created MySQL schema.
    use ApiTestTrait, CreatesAuthorLookups, WithoutMiddleware;

    /** @test */
    public function test_import_rejects_invalid_describe_locale() {
        $payload = [
            'name'            => 'Invalid Locale Author ' . uniqid(),
            'describe'        => 'A short description',
            'describe_locale' => 'fr'
        ];

        $response = $this->json('POST', '/api/v1/author/import', $payload);

        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);

        $this->assertSame('invalid', $body['message']);
        $this->assertSame(422, $body['code']);
        $this->assertArrayHasKey('describe_locale', $body['data']);
    }

    /** @test */
    public function test_create_new_author_supports_dynasty_id_and_nation_id() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $dynastyId = $this->createDynasty()->id;
        $nationId  = $this->createNation()->id;

        $response = Author::withoutEvents(function () use ($dynastyId, $nationId) {
            $payload = [
                'name'            => 'Author With Region ' . uniqid(),
                'describe'        => 'A short description',
                'describe_locale' => 'zh-CN',
                'dynasty_id'      => $dynastyId,
                'nation_id'       => $nationId,
            ];

            return $this->json('POST', '/api/v1/author/import', $payload);
        });

        /** @var \Illuminate\Testing\TestResponse $response */

        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);

        $this->assertEquals('created', $body['data']['status']);

        $author = Author::find($body['data']['author']['id']);
        $this->assertNotNull($author);
        $this->assertSame($dynastyId, $author->dynasty_id);
        $this->assertSame($nationId, $author->nation_id);
    }

    /** @test */
    public function test_import_rejects_soft_deleted_author_relation_id() {
        $dynasty = $this->createDynasty();
        $dynasty->delete();
        $authorCount = Author::count();

        $response = $this->json('POST', '/api/v1/author/import', [
            'name'       => 'Author With Deleted Dynasty ' . uniqid(),
            'dynasty_id' => $dynasty->id,
        ]);

        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);

        $this->assertSame(422, $body['code']);
        $this->assertArrayHasKey('dynasty_id', $body['data']);
        $this->assertSame($authorCount, Author::count());
    }

    /** @test */
    public function test_import_rejects_missing_author_relation_id() {
        $dynasty = $this->createDynasty();
        $dynasty->forceDelete();
        $authorCount = Author::count();

        $response = $this->json('POST', '/api/v1/author/import', [
            'name'       => 'Author With Missing Dynasty ' . uniqid(),
            'dynasty_id' => $dynasty->id,
        ]);

        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);

        $this->assertSame(422, $body['code']);
        $this->assertArrayHasKey('dynasty_id', $body['data']);
        $this->assertSame($authorCount, Author::count());
    }

    /** @test */
    public function test_import_accepts_explicit_null_author_relation_id() {
        $response = Author::withoutEvents(function () {
            return $this->json('POST', '/api/v1/author/import', [
                'name'       => 'Author Without Dynasty ' . uniqid(),
                'dynasty_id' => null,
            ]);
        });

        $response->assertStatus(200);
        $body = json_decode($response->getContent(), true);

        $this->assertSame('created', $body['data']['status']);
        $author = Author::find($body['data']['author']['id']);
        $this->assertNotNull($author);
        $this->assertNull($author->dynasty_id);
    }

    /** @test */
    public function test_import_with_unknown_wikidata_id_persists_author_relations() {
        $dynasty    = $this->createDynasty();
        $nation     = $this->createNation();
        $wikidataId = max(
            (int) Author::max('wikidata_id'),
            (int) Wikidata::max('id')
        ) + 1;

        $response = Author::withoutEvents(function () use ($dynasty, $nation, $wikidataId) {
            return $this->json('POST', '/api/v1/author/import', [
                'name'        => 'Author With Unknown Wikidata ' . uniqid(),
                'wikidata_id' => $wikidataId,
                'dynasty_id'  => $dynasty->id,
                'nation_id'   => $nation->id,
            ]);
        });

        $response->assertStatus(200);
        $body   = json_decode($response->getContent(), true);
        $author = Author::find($body['data']['author']['id']);

        $this->assertSame('created', $body['data']['status']);
        $this->assertSame($dynasty->id, $author->dynasty_id);
        $this->assertSame($nation->id, $author->nation_id);
    }

    /** @test */
    public function test_import_from_local_wikidata_persists_author_relations() {
        $dynasty    = $this->createDynasty();
        $nation     = $this->createNation();
        $wikidataId = max(
            (int) Author::max('wikidata_id'),
            (int) Wikidata::max('id')
        ) + 1;
        Wikidata::forceCreate([
            'id'   => $wikidataId,
            'type' => Wikidata::TYPE['poet'],
            'data' => json_encode([
                'labels'       => ['zh-CN' => ['value' => '本地 Wikidata 作者']],
                'descriptions' => new \stdClass(),
                'claims'       => new \stdClass(),
                'sitelinks'    => new \stdClass(),
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $response = Author::withoutEvents(function () use ($dynasty, $nation, $wikidataId) {
            return $this->json('POST', '/api/v1/author/import', [
                'name'        => 'Local Wikidata Author',
                'wikidata_id' => $wikidataId,
                'dynasty_id'  => $dynasty->id,
                'nation_id'   => $nation->id,
            ]);
        });

        $response->assertStatus(200);
        $body   = json_decode($response->getContent(), true);
        $author = Author::find($body['data']['author']['id']);

        $this->assertSame('created', $body['data']['status']);
        $this->assertSame($dynasty->id, $author->dynasty_id);
        $this->assertSame($nation->id, $author->nation_id);
    }

    /** @test */
    public function test_create_new_author_by_name() {
        // Disable model events to prevent alias:importFromAuthor command execution
        Author::withoutEvents(function () use (&$response) {
            $payload = [
                'name'            => 'Test Author ' . uniqid(),
                'describe'        => 'A short description',
                'describe_locale' => 'zh-CN'
            ];

            try {
                $response = $this->json('POST', '/api/v1/author/import', $payload);
            } catch (\Exception $e) {
                // Emit debug info to stderr so test runner shows it without changing vendor code
                file_put_contents('php://stderr', 'DEBUG EXCEPTION: ' . get_class($e) . ': ' . $e->getMessage() . "\n");
                if ($e instanceof \Illuminate\Database\QueryException) {
                    file_put_contents('php://stderr', 'SQL: ' . $e->getSql() . "\n");
                    file_put_contents('php://stderr', 'BINDINGS: ' . print_r($e->getBindings(), true) . "\n");
                }

                throw $e;
            }
        });

        $this->response = $response;
        $this->response->assertStatus(200);
        $body = json_decode($this->response->getContent(), true);
        $this->assertEquals('created', $body['data']['status']);
        $this->assertNotEmpty($body['data']['author']['id']);
    }

    /** @test */
    public function test_existing_author_by_wikidata() {
        // Disable model events to prevent alias:importFromAuthor command execution
        Author::withoutEvents(function () use (&$response, &$authorId) {
            // Create a test user for authentication (middleware disabled but controller may reference user)
            $user = factory(User::class)->create();
            $this->actingAs($user);

            // Use a unique wikidata_id for each test run to avoid conflicts
            $uniqueWikidataId = 9999999 + time() % 1000000;

            try {
                $authorId = DB::table('author')->insertGetId([
                    'name_lang'   => json_encode([config('app.locale', 'zh-CN') => 'Test Author']),
                    'wikidata_id' => $uniqueWikidataId,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                $author = Author::find($authorId);
            } catch (\Exception $e) {
                file_put_contents('php://stderr', 'FACTORY EXCEPTION: ' . get_class($e) . ': ' . $e->getMessage() . "\n");
                if ($e instanceof \Illuminate\Database\QueryException) {
                    file_put_contents('php://stderr', 'SQL: ' . $e->getSql() . "\n");
                    file_put_contents('php://stderr', 'BINDINGS: ' . print_r($e->getBindings(), true) . "\n");
                }

                throw $e;
            }

            $payload = [
                'name'        => $author->label,
                'wikidata_id' => $uniqueWikidataId
            ];

            $response = $this->json('POST', '/api/v1/author/import', $payload);
        });

        $this->response = $response;
        $this->response->assertStatus(200);
        $body = json_decode($this->response->getContent(), true);
        $this->assertEquals('existed', $body['data']['status']);
        $this->assertEquals($authorId, $body['data']['author']['id']);
    }

    /** @test */
    public function test_ambiguous_returns_candidates() {
        // Disable model events to prevent alias:importFromAuthor command execution
        Author::withoutEvents(function () use (&$response) {
            // Create a test user for authentication (middleware disabled but controller may reference user)
            $user = factory(User::class)->create();
            $this->actingAs($user);

            $name = 'Same Name ' . uniqid();

            try {
                DB::table('author')->insertGetId([
                    'name_lang'  => json_encode([config('app.locale', 'zh-CN') => $name]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                file_put_contents('php://stderr', 'FACTORY EXCEPTION A1: ' . get_class($e) . ': ' . $e->getMessage() . "\n");
                if ($e instanceof \Illuminate\Database\QueryException) {
                    file_put_contents('php://stderr', 'SQL: ' . $e->getSql() . "\n");
                    file_put_contents('php://stderr', 'BINDINGS: ' . print_r($e->getBindings(), true) . "\n");
                }

                throw $e;
            }

            try {
                DB::table('author')->insertGetId([
                    'name_lang'  => json_encode([config('app.locale', 'zh-CN') => $name]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                file_put_contents('php://stderr', 'FACTORY EXCEPTION A2: ' . get_class($e) . ': ' . $e->getMessage() . "\n");
                if ($e instanceof \Illuminate\Database\QueryException) {
                    file_put_contents('php://stderr', 'SQL: ' . $e->getSql() . "\n");
                    file_put_contents('php://stderr', 'BINDINGS: ' . print_r($e->getBindings(), true) . "\n");
                }

                throw $e;
            }

            $payload = ['name' => $name];

            $response = $this->json('POST', '/api/v1/author/import', $payload);
        });

        $this->response = $response;
        $this->response->assertStatus(200);
        $body = json_decode($this->response->getContent(), true);
        $this->assertEquals('ambiguous', $body['data']['status']);
        $this->assertNotEmpty($body['data']['candidates']);
    }

    private function getDefaultLocale() {
        return config('app.locale', 'zh-CN');
    }
}
