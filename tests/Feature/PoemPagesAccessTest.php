<?php

namespace Tests\Feature;

use App\Models\Poem;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\BuildsPoems;
use Tests\TestCase;

class PoemPagesAccessTest extends TestCase {
    use BuildsPoems, DatabaseTransactions;

    /** @dataProvider protectedPages */
    public function test_guests_must_log_in_before_accessing_poem_history_or_comparisons(string $path): void {
        $this->get($path)
            ->assertRedirect(route('login', ['ref' => $path]))
            ->assertSessionHas('url.intended', url($path));
    }

    /** @dataProvider protectedPages */
    public function test_guest_json_requests_require_authentication(string $path): void {
        $this->getJson($path)->assertUnauthorized();
    }

    public function test_posting_a_comparison_cannot_bypass_authentication(): void {
        $this->postJson('/compare/1,2')->assertUnauthorized();
    }

    /** @dataProvider protectedPages */
    public function test_front_controller_aliases_also_require_login(string $path): void {
        $this->withServerVariables([
            'SCRIPT_FILENAME' => '/var/www/public/index.php',
            'SCRIPT_NAME'     => '/index.php',
            'PHP_SELF'        => '/index.php' . $path,
        ])->get('/index.php' . $path)->assertRedirect(route('login', ['ref' => $path]));
    }

    public function test_login_redirects_preserve_the_target_page_and_query_for_wechat(): void {
        $path = '/compare/1,2?lang=en';
        $this->withHeader('User-Agent', 'MicroMessenger')->get($path)
            ->assertRedirect(route('login', ['ref' => $path]));
    }

    public function test_logged_in_users_can_read_poem_history_and_compare_poems(): void {
        $user = factory(User::class)->create([
            'email' => uniqid('poem-pages-', true) . '@example.com',
        ]);
        $poem = $this->makePoem($user, ['is_original' => 1]);
        $poem->update(['original_id' => $poem->id]);
        $translation = $this->makePoem($user, [
            'is_original' => 0,
            'original_id' => $poem->id,
        ]);

        $this->get('/p/' . $poem->fakeId)->assertOk();
        $this->actingAs($user);
        $this->get('/poems/contribution/' . $poem->fakeId)->assertOk();
        $this->get('/compare/' . $poem->id . ',' . $translation->id)->assertOk();
    }

    public function protectedPages(): array {
        return [
            'contributions' => ['/poems/contribution/' . Poem::getFakeId(1)],
            'one poem'      => ['/compare/1'],
            'comparison'    => ['/compare/1,2'],
            'three poems'   => ['/compare/1,2,3'],
        ];
    }
}
