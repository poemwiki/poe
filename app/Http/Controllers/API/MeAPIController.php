<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Poem;
use App\Models\Score;
use App\Repositories\PoemRepository;
use Illuminate\Support\Facades\Auth;

class MeAPIController extends Controller {
    /** @var PoemRepository */
    private PoemRepository $poemRepository;

    public function __construct(PoemRepository $poemRepository) {
        $this->poemRepository = $poemRepository;
    }

    /**
     * List poems that current user rated 5 stars (score=10 in score table).
     * Pagination size fixed to 10 for now.
     */
    public function fiveStarPoems($page = 1) {
        $user = Auth::user();
        if (!$user) {
            return $this->responseFail('unauthenticated');
        }

        $page = max(1, (int)$page);
        $pageSize = 10; // could be parameterized later

        // Get poem ids user scored 10
        $scoreQuery = Score::query()->where('user_id', $user->id)->where('score', 10);
        $poemIds = (clone $scoreQuery)->select('poem_id')->orderByDesc('updated_at');

        $paginator = Poem::query()
            ->whereIn('id', $poemIds)
            ->with([
                'reviews' => function ($q) {
                    $q->orderByDesc('created_at')
                        ->select(['id','poem_id','user_id','content','created_at'])
                        ->with('user:id,name,avatar');
                },
                'poetAuthor.user:id,name,is_v,avatar',
                'uploader:id,name,is_v,avatar'
            ])
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);

        // batch score counts
        $scoreCounts = Score::query()
            ->whereIn('poem_id', collect($paginator->items())->pluck('id'))
            ->selectRaw('poem_id, COUNT(user_id) as c')
            ->groupBy('poem_id')
            ->pluck('c','poem_id');

        $data = collect($paginator->items())->map(function (Poem $poem) use ($scoreCounts) {
            $poem['date_ago'] = \Illuminate\Support\Carbon::parse($poem->created_at)->diffForHumans(now());
            $poem['poet'] = $poem->poetLabel;
            $poem['score_count'] = (int)($scoreCounts[$poem->id] ?? 0);
            $poem['reviews'] = $poem->reviews->take(2)->map->only(PoemRepository::$relatedReviewColumns);
            $poem['poet_is_v'] = ($poem->is_owner_uploaded === Poem::$OWNER['uploader']) && $poem->uploader && $poem->uploader->is_v;
            $item = $poem->only(PoemRepository::$listColumns);
            $item['firstLine'] = $poem->firstLine;
            return $item;
        });

        return $this->responseSuccess([
            'data'           => $data,
            'total'          => $paginator->total(),
            'per_page'       => $paginator->perPage(),
            'current_page'   => $paginator->currentPage(),
            'last_page'      => $paginator->lastPage(),
            'has_more_pages' => $paginator->hasMorePages()
        ]);
    }
}
