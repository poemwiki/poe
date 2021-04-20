<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Listing;
use App\Http\Requests\Admin\Genre\BulkDestroyGenre;
use App\Http\Requests\Admin\Genre\DestroyGenre;
use App\Http\Requests\Admin\Genre\IndexGenre;
use App\Http\Requests\Admin\Genre\StoreGenre;
use App\Http\Requests\Admin\Genre\UpdateGenre;
use App\Models\Genre;
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

class GenreController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexGenre $request
     * @return array|Factory|View
     */
    public function index(IndexGenre $request)
    {
        // create and AdminListing instance for a specific model and
        $data = Listing::create(Genre::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['describe_lang', 'f_id', 'id', 'name', 'name_lang'],

            // set columns to searchIn
            ['describe_lang', 'id', 'name', 'name_lang', 'wikidata_id']
        );

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        return view('admin.genre.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.genre.create');

        return view('admin.genre.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreGenre $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreGenre $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Genre
        $genre = Genre::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/genres'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/genres');
    }

    /**
     * Display the specified resource.
     *
     * @param Genre $genre
     * @throws AuthorizationException
     * @return void
     */
    public function show(Genre $genre)
    {
        $this->authorize('admin.genre.show', $genre);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Genre $genre
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Genre $genre)
    {
        $this->authorize('admin.genre.edit', $genre);


        return view('admin.genre.edit', [
            'genre' => $genre,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateGenre $request
     * @param Genre $genre
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateGenre $request, Genre $genre)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Genre
        $genre->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/genres'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/genres');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyGenre $request
     * @param Genre $genre
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyGenre $request, Genre $genre)
    {
        $genre->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyGenre $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyGenre $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    DB::table('genres')->whereIn('id', $bulkChunk)
                        ->update([
                            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
