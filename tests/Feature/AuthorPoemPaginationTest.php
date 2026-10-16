<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Poem;
use App\Models\Relatable;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsPoems;
use Tests\TestCase;

class AuthorPoemPaginationTest extends TestCase {
    use BuildsPoems, DatabaseTransactions;

    public function test_readers_can_follow_pages_without_repeating_or_losing_equal_ranked_poems(): void {
        $author = $this->makeAuthor();
        $user = factory(User::class)->create();
        $ids = [];
        Poem::withoutEvents(function () use ($author, $user, &$ids) {
            for ($i = 0; $i < 53; $i++) {
                $ids[] = $this->makePoem($user, [
                    'poet_id' => $author->id,
                    'created_at' => '2026-10-01 12:00:00',
                ])->id;
            }
        });
        DB::table('score')->whereIn('poem_id', $ids)->delete();
        DB::table('poem')->whereIn('id', $ids)->update(['created_at' => '2026-10-01 12:00:00']);

        $first = $this->getJson($author->url . '?type=original');
        $first->assertOk()->assertJsonPath('total', 53);
        $this->assertCount(50, $this->poemIds($first->json('html')));

        $second = $this->getJson($first->json('next_url'));
        $second->assertOk()->assertJsonPath('next_url', null);
        $this->assertSame(array_reverse($ids), array_merge(
            $this->poemIds($first->json('html')),
            $this->poemIds($second->json('html'))
        ));
    }

    public function test_originals_include_owned_work_once_and_hide_deleted_or_merged_work(): void {
        $user = factory(User::class)->create();
        $author = $this->makeAuthor(['user_id' => $user->id]);
        $poems = Poem::withoutEvents(function () use ($author, $user) {
            return [
                $this->makePoem($user, ['poet_id' => $author->id, 'is_owner_uploaded' => Poem::$OWNER['uploader']]),
                $this->makePoem($user, ['is_owner_uploaded' => Poem::$OWNER['uploader']]),
                $this->makePoem($user, ['poet_id' => $author->id]),
                $this->makePoem($user, ['poet_id' => $author->id]),
                $this->makePoem($user),
            ];
        });
        $this->relate($poems[2], $author, Relatable::RELATION['merged_to_poem']);
        DB::table('poem')->where('id', $poems[3]->id)->update(['deleted_at' => now()]);

        $response = $this->getJson($author->url . '?type=original');
        $response->assertOk()->assertJsonPath('total', 2)->assertJsonPath('next_url', null);
        $this->assertEqualsCanonicalizing([$poems[0]->id, $poems[1]->id], $this->poemIds($response->json('html')));
    }

    public function test_translations_combine_legacy_and_related_authors_without_duplicates_or_wrong_types(): void {
        $author = $this->makeAuthor();
        $user = factory(User::class)->create();
        $poems = Poem::withoutEvents(function () use ($author, $user) {
            return [
                $this->makePoem($user, ['translator_id' => $author->id]),
                $this->makePoem($user),
                $this->makePoem($user),
                $this->makePoem($user),
            ];
        });
        $this->relate($poems[0], $author, Relatable::RELATION['translator_is']);
        $this->relate($poems[1], $author, Relatable::RELATION['translator_is']);
        $this->relate($poems[2], $author, Relatable::RELATION['translator_is'], ['end_type' => \App\Models\Entry::class]);
        $this->relate($poems[3], $author, Relatable::RELATION['translator_is'], ['start_type' => Author::class]);

        $response = $this->getJson($author->url . '?type=translation');
        $response->assertOk()->assertJsonPath('total', 2)->assertJsonPath('next_url', null);
        $this->assertEqualsCanonicalizing([$poems[0]->id, $poems[1]->id], $this->poemIds($response->json('html')));
    }

    public function test_hottest_ranks_by_weighted_rating_then_vote_count_then_weight_and_ignores_deleted_votes(): void {
        config(['app.score_min_count' => 2]);
        $author = $this->makeAuthor();
        $users = factory(User::class, 3)->create();
        $poems = Poem::withoutEvents(function () use ($author, $users) {
            $result = [];
            for ($i = 0; $i < 5; $i++) {
                $result[] = $this->makePoem($users[0], ['poet_id' => $author->id]);
            }

            return $result;
        });
        DB::table('score')->whereIn('poem_id', array_column($poems, 'id'))->delete();
        foreach ([[[10, 1], [2, 3]], [[6, 1], [6, 1]], [[10, 1]], [[6, 1], [6, 2]], [[6, 1], [6, 1], [6, 1]]] as $i => $votes) {
            foreach ($votes as $j => [$rating, $weight]) {
                DB::table('score')->insert([
                    'poem_id' => $poems[$i]->id, 'user_id' => $users[$j]->id,
                    'score' => $rating, 'weight' => $weight, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        DB::table('score')->insert([
            'poem_id' => $poems[0]->id, 'user_id' => $users[2]->id,
            'score' => 10, 'weight' => 100, 'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->getJson($author->url . '?sort=hottest');
        $response->assertOk();
        $this->assertSame([$poems[4]->id, $poems[3]->id, $poems[1]->id, $poems[0]->id, $poems[2]->id], $this->poemIds($response->json('html')));
    }

    public function test_newest_uses_writing_dates_and_creation_dates_with_real_month_ends(): void {
        $author = $this->makeAuthor();
        $user = factory(User::class)->create();
        $poems = Poem::withoutEvents(function () use ($author, $user) {
            $result = [];
            foreach ([['year' => 2024, 'month' => 2], ['year' => 2024, 'month' => 2, 'date' => 29], ['year' => 2024, 'month' => 2, 'date' => 28], ['year' => 2024], [], ['year' => 9], ['year' => -44]] as $date) {
                $result[] = $this->makePoem($user, array_merge(['poet_id' => $author->id], $date));
            }

            return $result;
        });
        DB::table('poem')->whereIn('id', array_column($poems, 'id'))->update(['created_at' => '2025-01-01 12:00:00']);

        $response = $this->getJson($author->url . '?sort=newest');
        $response->assertOk();
        $this->assertSame([$poems[4]->id, $poems[3]->id, $poems[1]->id, $poems[0]->id, $poems[2]->id, $poems[5]->id, $poems[6]->id], $this->poemIds($response->json('html')));
    }

    public function test_invalid_pagination_is_rejected_and_pages_past_the_end_are_empty(): void {
        $author = $this->makeAuthor();
        foreach (['page=0', 'page=-1', 'page=abc', 'page[]=1', 'sort=unknown', 'type=unknown'] as $query) {
            $this->getJson($author->url . '?' . $query)->assertUnprocessable();
        }
        $response = $this->getJson($author->url . '?page=2');
        $response->assertOk()->assertJsonPath('total', 0)->assertJsonPath('next_url', null);
        $this->assertSame([], $this->poemIds($response->json('html')));
    }

    private function relate(Poem $poem, Author $author, int $relation, array $overrides = []): void {
        DB::table('relatable')->insert(array_merge([
            'start_type' => Poem::class, 'start_id' => $poem->id,
            'end_type' => Author::class, 'end_id' => $author->id,
            'relation' => $relation, 'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }

    private function makeAuthor(array $attributes = []): Author {
        $id = DB::table('author')->insertGetId(array_merge([
            'name_lang' => json_encode(['zh-CN' => '分页测试作者']),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Author::findOrFail($id);
    }

    private function poemIds(string $html): array {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $nodes = (new \DOMXPath($document))->query('//li[@data-poem-id]');
        $ids = [];
        foreach ($nodes as $node) {
            $ids[] = (int) $node->getAttribute('data-poem-id');
        }

        return $ids;
    }
}
