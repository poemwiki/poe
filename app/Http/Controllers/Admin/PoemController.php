<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Poem\BulkDestroyPoem;
use App\Http\Requests\Admin\Poem\DestroyPoem;
use App\Http\Requests\Admin\Poem\IndexPoem;
use App\Http\Requests\Admin\Poem\StorePoem;
use App\Http\Requests\Admin\Poem\UpdatePoem;
use App\Models\Poem;
use App\Http\Listing;
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

class PoemController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexPoem $request
     * @return array|Factory|View
     */
    public function index(IndexPoem $request)
    {
        // create and AdminListing instance for a specific model and
        $data = Listing::create(Poem::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['id', 'title', 'updated_at', 'language_id', 'is_original', 'poet', 'poet_cn', 'bedtime_post_id', 'bedtime_post_title', 'length', 'translator', 'from', 'year', 'month', 'date', 'location', 'dynasty', 'nation', 'need_confirm', 'is_lock', 'content_id'],

            // set columns to searchIn
            ['id', 'title', 'poet', 'poet_cn', 'bedtime_post_title', 'poem', 'translator', 'from', 'year', 'month', 'date', 'dynasty', 'nation'],

            function ($query) use ($request) {
                if(!$request->input('orderBy'))
                    $query->orderBy('updated_at', 'desc');
            }

        );

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        return view('admin.poem.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.poem.create');

        return view('admin.poem.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePoem $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StorePoem $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Poem
        $poem = Poem::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/poems'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/poems');
    }

    /**
     * Display the specified resource.
     *
     * @param Poem $poem
     * @throws AuthorizationException
     * @return void
     */
    public function show(Poem $poem)
    {
        $this->authorize('admin.poem.show', $poem);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Poem $poem
     * @return \Illuminate\Contracts\Foundation\Application|RedirectResponse|Redirector
     * @throws AuthorizationException
     */
    public function edit(Poem $poem)
    {
        return redirect(route('poems/edit', $poem->fakeId));
        // $this->authorize('admin.poem.edit', $poem);
        //
        //
        // return view('admin.poem.edit', [
        //     'poem' => $poem,
        // ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePoem $request
     * @param Poem $poem
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdatePoem $request, Poem $poem)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Poem
        $poem->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/poems'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/poems');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyPoem $request
     * @param Poem $poem
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyPoem $request, Poem $poem)
    {
        $poem->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyPoem $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyPoem $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    DB::table('poems')->whereIn('id', $bulkChunk)
                        ->update([
                            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
