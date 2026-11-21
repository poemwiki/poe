<?php

namespace Tests\APIs;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\BuildsPoems;
use Tests\TestCase;

class PoemTranslationFamilyApiTest extends TestCase {
    use BuildsPoems, DatabaseTransactions;

    public function test_original_and_translation_details_expose_all_related_versions(): void {
        $user     = factory(User::class)->create();
        $original = $this->makePoem($user, ['is_original' => 1]);
        $original->update(['original_id' => $original->id]);

        $sibling  = $this->makePoem($user, ['is_original' => 0, 'original_id' => $original->id]);
        $versions = [$sibling->id];
        $source   = $original;
        for ($depth = 0; $depth < 5; $depth++) {
            $source     = $this->makePoem($user, ['is_original' => 0, 'original_id' => $source->id]);
            $versions[] = $source->id;
        }

        $originalFamily = $this->getJson('/api/v1/poem/detail/' . $original->id)
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.translation_family.original', null)
            ->json('data.translation_family');
        $this->assertEqualsCanonicalizing($versions, array_column($originalFamily['translations'], 'id'));

        $translationFamily = $this->getJson('/api/v1/poem/detail/' . $source->id)
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.translation_family.original.id', $original->id)
            ->json('data.translation_family');
        $this->assertEqualsCanonicalizing(array_slice($versions, 0, -1), array_column($translationFamily['translations'], 'id'));
    }

    public function test_unlinked_translation_has_no_original_link(): void {
        $user = factory(User::class)->create();
        $poem = $this->makePoem($user, ['is_original' => 0, 'original_id' => 0]);

        $this->getJson('/api/v1/poem/detail/' . $poem->id)
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.translation_family.original', null)
            ->assertJsonPath('data.translation_family.translations', []);
    }
}
