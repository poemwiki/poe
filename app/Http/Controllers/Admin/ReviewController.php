<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Listing;
use App\Http\Requests\Admin\Review\BulkDestroyReview;
use App\Http\Requests\Admin\Review\DestroyReview;
use App\Http\Requests\Admin\Review\IndexReview;
use App\Http\Requests\Admin\Review\StoreReview;
use App\Http\Requests\Admin\Review\UpdateReview;
use App\Models\Review;
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

class ReviewController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexReview $request
     * @return array|Factory|View
     */
    public function index(IndexReview $request)
    {
        // create and AdminListing instance for a specific model and
        $data = Listing::create(Review::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['content', 'id', 'like', 'poem_id', 'poem.title as poem_title', 'title', 'user_id', 'users.name as user_name', 'updated_at'],

            // set columns to searchIn
            ['content', 'id', 'title', 'user_id', 'users.name', 'poem.title'],


            function ($query) use ($request) {
                if(!$request->input('orderBy'))
                    $query->orderBy('updated_at', 'desc');

                // $query->with('user');
                $query->leftJoin('users', 'review.user_id', '=', 'users.id');
                $query->leftJoin('poem', 'review.poem_id', '=', 'poem.id');
            }
        );

        foreach ($data as &$review) {
            $review['poem_url'] = $review->poem ? $review->poem->url : null;
        }

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        return view('admin.review.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.review.create');

        return view('admin.review.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreReview $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreReview $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Review
        $review = Review::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/reviews'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/reviews');
    }

    /**
     * Display the specified resource.
     *
     * @param Review $review
     * @throws AuthorizationException
     * @return void
     */
    public function show(Review $review)
    {
        $this->authorize('admin.review.show', $review);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Review $review
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Review $review)
    {
        $this->authorize('admin.review.edit', $review);


        return view('admin.review.edit', [
            'review' => $review,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateReview $request
     * @param Review $review
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateReview $request, Review $review)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Review
        $review->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/reviews'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/reviews');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyReview $request
     * @param Review $review
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyReview $request, Review $review)
    {
        $review->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyReview $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyReview $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    DB::table('reviews')->whereIn('id', $bulkChunk)
                        ->update([
                            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
