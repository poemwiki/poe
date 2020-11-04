<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Score\BulkDestroyScore;
use App\Http\Requests\Admin\Score\DestroyScore;
use App\Http\Requests\Admin\Score\IndexScore;
use App\Http\Requests\Admin\Score\StoreScore;
use App\Http\Requests\Admin\Score\UpdateScore;
use App\Models\Score;
use Brackets\AdminListing\Facades\AdminListing;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScoreController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexScore $request
     * @return array|Factory|View
     */
    public function index(IndexScore $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Score::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['id', 'poem_id', 'score', 'user_id', 'weight'],

            // set columns to searchIn
            ['id']
        );

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        return view('admin.score.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.score.create');

        return view('admin.score.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreScore $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreScore $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Score
        $score = Score::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/scores'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/scores');
    }

    /**
     * Display the specified resource.
     *
     * @param Score $score
     * @throws AuthorizationException
     * @return void
     */
    public function show(Score $score)
    {
        $this->authorize('admin.score.show', $score);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Score $score
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Score $score)
    {
        $this->authorize('admin.score.edit', $score);


        return view('admin.score.edit', [
            'score' => $score,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateScore $request
     * @param Score $score
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateScore $request, Score $score)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Score
        $score->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/scores'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/scores');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyScore $request
     * @param Score $score
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyScore $request, Score $score)
    {
        $score->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyScore $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyScore $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    DB::table('scores')->whereIn('id', $bulkChunk)
                        ->update([
                            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
