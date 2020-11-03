<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Author\BulkDestroyAuthor;
use App\Http\Requests\Admin\Author\DestroyAuthor;
use App\Http\Requests\Admin\Author\IndexAuthor;
use App\Http\Requests\Admin\Author\StoreAuthor;
use App\Http\Requests\Admin\Author\UpdateAuthor;
use App\Models\Author;
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

class AuthorController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexAuthor $request
     * @return array|Factory|View
     */
    public function index(IndexAuthor $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Author::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['describe_lang', 'id', 'name_lang', 'pic_url', 'user_id', 'wikipedia_url'],

            // set columns to searchIn
            ['describe_lang', 'id', 'name_lang', 'pic_url', 'wikidata_id', 'wikipedia_url']
        );

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        return view('admin.author.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.author.create');

        return view('admin.author.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreAuthor $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreAuthor $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Author
        $author = Author::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/authors'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/authors');
    }

    /**
     * Display the specified resource.
     *
     * @param Author $author
     * @throws AuthorizationException
     * @return void
     */
    public function show(Author $author)
    {
        $this->authorize('admin.author.show', $author);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Author $author
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Author $author)
    {
        $this->authorize('admin.author.edit', $author);


        return view('admin.author.edit', [
            'author' => $author,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateAuthor $request
     * @param Author $author
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateAuthor $request, Author $author)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Author
        $author->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/authors'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/authors');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyAuthor $request
     * @param Author $author
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyAuthor $request, Author $author)
    {
        $author->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyAuthor $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyAuthor $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    DB::table('authors')->whereIn('id', $bulkChunk)
                        ->update([
                            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
