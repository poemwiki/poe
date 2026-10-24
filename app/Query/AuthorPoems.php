<?php

namespace App\Query;

use App\Models\Author;
use App\Models\Poem;
use App\Models\Relatable;
use App\Models\Score;
use Illuminate\Pagination\LengthAwarePaginator;

class AuthorPoems {
    public function paginate(Author $author, string $type, string $sort, int $page = 1): LengthAwarePaginator {
        $query = Poem::query()->whereNotExists(function ($merged) {
            $merged->selectRaw('1')->from('relatable')
                ->whereColumn('relatable.start_id', 'poem.id')
                ->where('relatable.relation', Relatable::RELATION['merged_to_poem']);
        });

        $query->where(function ($works) use ($author, $type) {
            if ($type === 'original') {
                $works->where('poem.poet_id', $author->id);
                if ($author->user_id) {
                    $works->orWhere(function ($owned) use ($author) {
                        $owned->where('poem.upload_user_id', $author->user_id)
                            ->where('poem.is_owner_uploaded', Poem::$OWNER['uploader']);
                    });
                }
            } else {
                $works->where('poem.translator_id', $author->id)
                    ->orWhereExists(function ($related) use ($author) {
                        $related->selectRaw('1')->from('relatable')
                            ->whereColumn('relatable.start_id', 'poem.id')
                            ->where('relatable.start_type', Poem::class)
                            ->where('relatable.end_type', Author::class)
                            ->where('relatable.end_id', $author->id)
                            ->where('relatable.relation', Relatable::RELATION['translator_is']);
                    });
            }
        });

        if ($sort === 'newest') {
            // Incomplete writing dates sort at the end of their year/month, as on the existing author page.
            $query->orderByRaw('CAST(COALESCE(NULLIF(poem.year, 0), YEAR(poem.created_at)) AS SIGNED) DESC')
                ->orderByRaw('CAST(CASE WHEN NULLIF(poem.year, 0) IS NULL THEN MONTH(poem.created_at) ELSE COALESCE(NULLIF(poem.month, 0), 12) END AS SIGNED) DESC')
                ->orderByRaw("CAST(CASE WHEN NULLIF(poem.year, 0) IS NULL THEN DAY(poem.created_at) ELSE COALESCE(NULLIF(poem.date, 0), DAY(LAST_DAY(CONCAT(poem.year, '-', COALESCE(NULLIF(poem.month, 0), 12), '-01'))), 31) END AS SIGNED) DESC")
                ->orderByRaw('CASE WHEN NULLIF(poem.year, 0) IS NULL THEN TIME_TO_SEC(poem.created_at) ELSE 86399 END DESC');
        } else {
            // Aggregate in SQL so ranking never hydrates every poem or repeatedly parses dates in PHP.
            $scores = Score::query()->select('poem_id')
                ->selectRaw('COUNT(*) AS vote_count, SUM(weight) AS vote_weight')
                ->selectRaw('CASE WHEN COUNT(*) >= ? THEN ROUND(SUM(score * weight) / NULLIF(SUM(weight), 0), 1) END AS rating', [config('app.score_min_count')])
                ->groupBy('poem_id');
            $query->leftJoinSub($scores, 'author_scores', 'author_scores.poem_id', '=', 'poem.id')
                ->orderByDesc('author_scores.rating')
                ->orderByRaw('CASE WHEN author_scores.rating IS NOT NULL THEN author_scores.vote_count END DESC')
                ->orderByRaw('CASE WHEN author_scores.rating IS NOT NULL THEN author_scores.vote_weight END DESC');
        }

        // Break ties deterministically across pages while the underlying data is unchanged.
        return $query->orderByDesc('poem.created_at')->orderByDesc('poem.id')
            ->paginate(50, ['poem.*'], 'page', $page)
            ->withPath(route('author/show', $author->fakeId))
            ->appends(['type' => $type, 'sort' => $sort]);
    }
}
