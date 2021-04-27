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
            ['id', 'title', 'updated_at', 'created_at', 'is_original', 'length',
                'poet', 'poet_cn', 'poet_id', 'poetAuthor.name_lang',
                'translator', 'translator_id', 'translatorAuthor.name_lang', 'from', 'language_id', 'language.name_lang',
                'is_owner_uploaded', 'upload_user_id', 'uploader.name as uploader_name', 'need_confirm', 'is_lock', 'content_id'],

            // set columns to searchIn
            ['id', 'title', 'poet', 'poet_cn', 'poetAuthor.name_lang', 'translatorAuthor.name_lang', 'bedtime_post_title', 'poem', 'uploader.name', 'translator', 'from'],

            function ($query) use ($request) {
                if(!$request->input('orderBy'))
                    $query->orderBy('updated_at', 'desc');

                $query->leftJoin('users as uploader', 'uploader.id', '=', 'poem.upload_user_id');
                $query->leftJoin('author as poetAuthor', 'poetAuthor.id', '=', 'poem.poet_id');
                $query->leftJoin('author as translatorAuthor', 'translatorAuthor.id', '=', 'poem.translator_id');
                $query->leftJoin('language', 'language.id', '=', 'poem.language_id');
            }

        );

        foreach ($data as &$poem) {
            $poem['url'] = $poem->url;
            $poem['language_name'] = $poem->lang ? $poem->lang->label : '';
            $poem['poet_label'] = $poem->poetLabel;
            if($poem->poetAuthor) {
                $poem['poet_url'] = $poem->poetAuthor->url;
            }
            $poem['translator_label'] = $poem->translatorLabel;
            if($poem->translatorAuthor) {
                $poem['translator_url'] = $poem->translatorAuthor->url;
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
        if($poem->translatedPoems->count()) {
            $ids = $poem->translatedPoems->pluck('id')->toArray();
            return response(['message' => '删除失败，请先删除本诗关联的译作(ID:'.join(', ', $ids).')'], 405);
        }
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
