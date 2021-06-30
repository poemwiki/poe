<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Author\BulkDestroyAuthor;
use App\Http\Requests\Admin\Author\DestroyAuthor;
use App\Http\Requests\Admin\Author\IndexAuthor;
use App\Http\Requests\Admin\Author\StoreAuthor;
use App\Http\Requests\Admin\Author\UpdateAuthor;
use App\Models\Author;
use App\Models\Dynasty;
use App\Models\Nation;
use App\Http\Listing;
use Brackets\AdminAuth\Models\AdminUser;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthorController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @param IndexAuthor $request
     * @return array|Factory|View
     */
    public function index(IndexAuthor $request) {
        // create and AdminListing instance for a specific model and
        $data = Listing::create(Author::class)->processRequestAndGet(
        // pass the request with params
            $request,

            // set columns to query
            ['id', 'name_lang', 'wikidata_id', 'user_id', 'updated_at', 'created_at', 'authorUser.name as user_name',
                'upload_user_id', 'uploader.name as uploader_name', 'log.causer_type as causer_type', 'log.causer_id as causer_id'
            ],

            // set columns to searchIn
            ['name_lang', 'id', 'authorUser.name', 'wikidata_id', 'user_id', 'uploader.name'],

            function (Builder $query) use ($request) {
                if(!$request->input('orderBy'))
                    $query->orderBy('updated_at', 'desc');

                $query->leftJoin('users as authorUser', 'author.user_id', '=', 'authorUser.id');
                $query->leftJoin('users as uploader', 'author.upload_user_id', '=', 'uploader.id');
                $query->leftJoin('activity_log as log', function (JoinClause $join) {
                    $join->on('author.id', '=', 'log.subject_id')
                        ->where([
                            ['log.subject_type', '=', Author::class],
                            ['log.description', '=', 'created']
                        ])
                    ;
                });
            },

            app()->getLocale()
        );

        foreach ($data as &$author) {
            if ($author->causer_type === AdminUser::class && $author->causer_id) {
                $adminUser = AdminUser::find($author->causer_id);
                if($adminUser)
                    $author->uploader_name = $adminUser->email . '[后台用户]';
            }
        }

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
     * @return \Illuminate\Contracts\Foundation\Application|RedirectResponse|Redirector
     * @throws AuthorizationException
     */
    public function create() {

        return redirect(route('author/create'));

        // $this->authorize('admin.author.create');
        //
        // return view('admin.author.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreAuthor $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreAuthor $request) {
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
     * @return void
     * @throws AuthorizationException
     */
    public function show(Author $author) {
        $this->authorize('admin.author.show', $author);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Author $author
     * @return \Illuminate\Contracts\Foundation\Application|RedirectResponse|Redirector
     * @throws AuthorizationException
     */
    public function edit(Author $author) {

        return redirect(route('author/edit', $author->fakeId));

        // $this->authorize('admin.author.edit', $author);
        //
        // return view('admin.author.edit', [
        //     'author' => $author,
        //     'nationList' => Nation::select('name_lang', 'id')->get(),
        //     'dynastyList' => Dynasty::select('name_lang', 'id')->get(),
        // ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Author $author
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function verify(Author $author) {
        $this->authorize('admin.author.edit', $author);

        return view('admin.author.verify', [
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
    public function update(UpdateAuthor $request, Author $author) {
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
     * @return ResponseFactory|RedirectResponse|Response
     * @throws Exception
     */
    public function destroy(DestroyAuthor $request, Author $author) {
        if($author->poems->count() || $author->translatedPoems->count()) {
            $ids = [];
            foreach ($author->poems as $p) {
                $ids[] = $p->id;
            }
            foreach ($author->translatedPoems as $p) {
                $ids[] = $p->id;
            }
            return response(['message' => '删除失败，请先删除本作者关联的作品(ID:'.join(', ', $ids).')'], 405);
        }

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
     * @return Response|bool
     * @throws Exception
     */
    public function bulkDestroy(BulkDestroyAuthor $request): Response {
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
