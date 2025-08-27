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
}
