<?php namespace Tests\APIs;

use App\Repositories\CampaignRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignIndexApiTest extends TestCase {
    use DatabaseTransactions;

    protected function setUp(): void {
        parent::setUp();

        Cache::flush();
        DB::table('campaign')->whereNull('deleted_at')->update([
            'settings' => json_encode(['test' => 1]),
        ]);
    }

    protected function tearDown(): void {
        Cache::flush();

        parent::tearDown();
    }

    /** @test */
    public function index_discovers_a_campaign_inserted_after_an_empty_result() {
        $firstResponse = $this->json('GET', '/api/v1/campaign');
        $firstResponse->assertStatus(200);
        $this->assertSame([], $firstResponse->json('data'));

        $campaignId = $this->insertCampaign('2026-08-01 00:00:00');

        $secondResponse = $this->json('GET', '/api/v1/campaign');
        $secondResponse->assertStatus(200);
        $this->assertSame([$campaignId], array_column($secondResponse->json('data'), 'id'));
    }

    /** @test */
    public function index_applies_visibility_filter_before_the_result_limit() {
        $visibleCampaignId = $this->insertCampaign('2026-01-01 00:00:00');

        for ($day = 1; $day <= 15; $day++) {
            $this->insertCampaign(
                sprintf('2027-01-%02d 00:00:00', $day),
                ['test' => 1]
            );
        }

        $response = $this->json('GET', '/api/v1/campaign');
        $response->assertStatus(200);
        $this->assertSame([$visibleCampaignId], array_column($response->json('data'), 'id'));
    }

    /** @test */
    public function showing_a_visible_campaign_clears_a_legacy_empty_index_cache() {
        $campaignId = $this->insertCampaign('2027-06-01 00:00:00');
        Cache::tags(['campaign-index'])->put('api-campaign-index-0-15', collect(), 3600);

        $showResponse = $this->json('GET', '/api/v1/campaign/show/' . $campaignId);
        $showResponse->assertStatus(200);
        $this->assertSame(0, $showResponse->json('code'));
        $this->assertNull(Cache::tags(['campaign-index'])->get('api-campaign-index-0-15'));

        $indexResponse = $this->json('GET', '/api/v1/campaign');
        $indexResponse->assertStatus(200);
        $this->assertSame([$campaignId], array_column($indexResponse->json('data'), 'id'));
    }

    /** @test */
    public function pages_keep_campaigns_with_the_same_start_in_a_stable_order() {
        $ids = [];
        for ($i = 0; $i < 4; $i++) {
            $ids[] = $this->insertCampaign('2027-06-01 00:00:00');
        }

        $repository = app(CampaignRepository::class);
        $firstPage  = $repository->paginatedIndex(0, 2);
        $secondPage = $repository->paginatedIndex(2, 2);

        $this->assertSame(array_reverse($ids), $firstPage->concat($secondPage)->pluck('id')->all());
    }

    private function insertCampaign(string $start, array $settings = []): int {
        $name  = 'Campaign index test ' . uniqid('', true);
        $tagId = DB::table('tag')->insertGetId([
            'name'          => $name,
            'name_lang'     => json_encode(['zh-CN' => $name]),
            'describe_lang' => json_encode([]),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return DB::table('campaign')->insertGetId([
            'start'         => $start,
            'end'           => date('Y-m-d H:i:s', strtotime($start . ' +7 days')),
            'image'         => 'campaign/test.webp',
            'name_lang'     => json_encode(['zh-CN' => $name]),
            'describe_lang' => json_encode([]),
            'tag_id'        => $tagId,
            'settings'      => json_encode($settings),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}
