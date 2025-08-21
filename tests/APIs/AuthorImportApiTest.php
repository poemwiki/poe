<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestTrait;
use App\Models\Author;
use App\User;

class AuthorImportApiTest extends TestCase
{
    // Removed RefreshDatabase to avoid running migrations; using pre-created MySQL schema.
    use ApiTestTrait, WithoutMiddleware;

    /** @test */
    public function test_create_new_author_by_name()
    {
        // Disable model events to prevent alias:importFromAuthor command execution
        Author::withoutEvents(function () use (&$response) {
            $payload = [
                'name' => 'Test Author ' . uniqid(),
                'describe' => 'A short description',
                'describe_locale' => 'zh-CN'
            ];

            try {
                $response = $this->json('POST', '/api/v1/author/import', $payload);
            } catch (\Exception $e) {
                // Emit debug info to stderr so test runner shows it without changing vendor code
                file_put_contents('php://stderr', "DEBUG EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n");
                if ($e instanceof \Illuminate\Database\QueryException) {
                    file_put_contents('php://stderr', "SQL: " . $e->getSql() . "\n");
                    file_put_contents('php://stderr', "BINDINGS: " . print_r($e->getBindings(), true) . "\n");
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
    public function test_existing_author_by_wikidata()
    {
        // Disable model events to prevent alias:importFromAuthor command execution
        Author::withoutEvents(function () use (&$response, &$authorId) {
            // Create a test user for authentication (middleware disabled but controller may reference user)
            $user = factory(User::class)->create();
            $this->actingAs($user);

            // Use a unique wikidata_id for each test run to avoid conflicts
            $uniqueWikidataId = 9999999 + time() % 1000000;
            
            try {
                $authorId = DB::table('author')->insertGetId([
                    'name_lang'  => json_encode([config('app.locale', 'zh-CN') => 'Test Author']),
                    'wikidata_id'=> $uniqueWikidataId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $author = Author::find($authorId);
            } catch (\Exception $e) {
                file_put_contents('php://stderr', "FACTORY EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . "\n");
                if ($e instanceof \Illuminate\Database\QueryException) {
                    file_put_contents('php://stderr', "SQL: " . $e->getSql() . "\n");
                    file_put_contents('php://stderr', "BINDINGS: " . print_r($e->getBindings(), true) . "\n");
                }
                throw $e;
            }

            $payload = [
                'name' => $author->label,
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
    public function test_ambiguous_returns_candidates()
    {
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
                file_put_contents('php://stderr', "FACTORY EXCEPTION A1: " . get_class($e) . ": " . $e->getMessage() . "\n");
                if ($e instanceof \Illuminate\Database\QueryException) {
                    file_put_contents('php://stderr', "SQL: " . $e->getSql() . "\n");
                    file_put_contents('php://stderr', "BINDINGS: " . print_r($e->getBindings(), true) . "\n");
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
                file_put_contents('php://stderr', "FACTORY EXCEPTION A2: " . get_class($e) . ": " . $e->getMessage() . "\n");
                if ($e instanceof \Illuminate\Database\QueryException) {
                    file_put_contents('php://stderr', "SQL: " . $e->getSql() . "\n");
                    file_put_contents('php://stderr', "BINDINGS: " . print_r($e->getBindings(), true) . "\n");
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
