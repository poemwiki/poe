<?php namespace Tests\Unit;

use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PoemRepositoryTest extends TestCase {
    use DatabaseTransactions;

    protected function setUp(): void {
        parent::setUp();
        // ensure repository can be resolved (sanity check)
        $repo = app(PoemRepository::class);
        $this->assertInstanceOf(PoemRepository::class, $repo);
    }

    /** @test */
    public function random_with_max_id_should_return_id_less_or_equal_than_maxId() {
        factory(Poem::class, 15)->create();
        $poems  = Poem::all();
        $maxId  = $poems->max('id');
        $cutoff = $maxId - 10; // pick a cutoff below current maximum
        $this->assertGreaterThan(0, $cutoff, 'Cutoff must be positive');

        for ($i = 0; $i < 3; $i++) {
            $poem = PoemRepository::random([], null, ['poem.id'], $cutoff)->first();
            $this->assertNotNull($poem, 'Random query should return a poem');
            $this->assertLessThanOrEqual($cutoff, $poem->id, 'Returned poem id should be <= provided maxId');
        }
    }

    /** @test */
    public function user_suggested_ids_caching_works() {
        // Test with logged-in user identifier
        $userIdentifier = 'user:123';
        $poemIds = [1, 2, 3, 4, 5];

        // Test adding suggested IDs
        PoemRepository::addUserSuggestedIds($userIdentifier, $poemIds);
        
        // Test retrieving suggested IDs
        $cachedIds = PoemRepository::getUserSuggestedIds($userIdentifier);
        $this->assertEquals($poemIds, $cachedIds, 'Cached IDs should match added IDs');

        // Test adding more IDs (should merge and deduplicate)
        $morePoemIds = [4, 5, 6, 7];
        PoemRepository::addUserSuggestedIds($userIdentifier, $morePoemIds);
        
        $allCachedIds = PoemRepository::getUserSuggestedIds($userIdentifier);
        $this->assertContains(1, $allCachedIds, 'Should contain original IDs');
        $this->assertContains(6, $allCachedIds, 'Should contain new IDs');
        $this->assertCount(7, $allCachedIds, 'Should have 7 unique IDs');

        // Test with empty user identifier
        $emptyResult = PoemRepository::getUserSuggestedIds(null);
        $this->assertEquals([], $emptyResult, 'Should return empty array for null user identifier');
    }

    /** @test */
    public function anonymous_user_caching_works() {
        // Test with anonymous user (session-based) identifier
        $sessionIdentifier = 'session:abc123def456';
        $poemIds = [10, 20, 30];

        // Test adding suggested IDs for anonymous user
        PoemRepository::addUserSuggestedIds($sessionIdentifier, $poemIds);
        
        // Test retrieving suggested IDs
        $cachedIds = PoemRepository::getUserSuggestedIds($sessionIdentifier);
        $this->assertEquals($poemIds, $cachedIds, 'Anonymous user cached IDs should match added IDs');

        // Test that different session identifiers have separate caches
        $anotherSessionId = 'session:xyz789ghi012';
        $otherPoemIds = [40, 50, 60];
        PoemRepository::addUserSuggestedIds($anotherSessionId, $otherPoemIds);

        $firstSessionIds = PoemRepository::getUserSuggestedIds($sessionIdentifier);
        $secondSessionIds = PoemRepository::getUserSuggestedIds($anotherSessionId);

        $this->assertEquals($poemIds, $firstSessionIds, 'First session IDs should remain unchanged');
        $this->assertEquals($otherPoemIds, $secondSessionIds, 'Second session should have different IDs');
        $this->assertNotEquals($firstSessionIds, $secondSessionIds, 'Different sessions should have different cached IDs');
    }

    /** @test */
    public function weapp_user_caching_works() {
        // Test with WeChat mini-program user identifier
        $weappIdentifier = 'weapp:wx_client_abc123';
        $poemIds = [100, 200, 300];

        // Test adding suggested IDs for WeChat mini-program user
        PoemRepository::addUserSuggestedIds($weappIdentifier, $poemIds);
        
        // Test retrieving suggested IDs
        $cachedIds = PoemRepository::getUserSuggestedIds($weappIdentifier);
        $this->assertEquals($poemIds, $cachedIds, 'WeApp user cached IDs should match added IDs');

        // Test that different client IDs have separate caches
        $anotherWeappId = 'weapp:wx_client_def456';
        $otherPoemIds = [400, 500, 600];
        PoemRepository::addUserSuggestedIds($anotherWeappId, $otherPoemIds);

        $firstWeappIds = PoemRepository::getUserSuggestedIds($weappIdentifier);
        $secondWeappIds = PoemRepository::getUserSuggestedIds($anotherWeappId);

        $this->assertEquals($poemIds, $firstWeappIds, 'First WeApp client IDs should remain unchanged');
        $this->assertEquals($otherPoemIds, $secondWeappIds, 'Second WeApp client should have different IDs');
        $this->assertNotEquals($firstWeappIds, $secondWeappIds, 'Different WeApp clients should have different cached IDs');
    }

    /** @test */
    public function cloudflare_ip_based_caching_works() {
        // Test with web user using Cloudflare IP
        $webCfIdentifier = 'web:cf_' . md5('192.168.1.1');
        $poemIds = [1000, 2000, 3000];

        // Test adding suggested IDs for CF IP based user
        PoemRepository::addUserSuggestedIds($webCfIdentifier, $poemIds);
        
        // Test retrieving suggested IDs
        $cachedIds = PoemRepository::getUserSuggestedIds($webCfIdentifier);
        $this->assertEquals($poemIds, $cachedIds, 'CF IP based user cached IDs should match added IDs');

        // Test that different IPs have separate caches
        $anotherWebCfId = 'web:cf_' . md5('10.0.0.1');
        $otherPoemIds = [4000, 5000, 6000];
        PoemRepository::addUserSuggestedIds($anotherWebCfId, $otherPoemIds);

        $firstWebCfIds = PoemRepository::getUserSuggestedIds($webCfIdentifier);
        $secondWebCfIds = PoemRepository::getUserSuggestedIds($anotherWebCfId);

        $this->assertEquals($poemIds, $firstWebCfIds, 'First CF IP IDs should remain unchanged');
        $this->assertEquals($otherPoemIds, $secondWebCfIds, 'Second CF IP should have different IDs');
        $this->assertNotEquals($firstWebCfIds, $secondWebCfIds, 'Different CF IPs should have different cached IDs');
    }
}
