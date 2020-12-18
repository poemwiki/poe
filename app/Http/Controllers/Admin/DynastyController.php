<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dynasty\BulkDestroyDynasty;
use App\Http\Requests\Admin\Dynasty\DestroyDynasty;
use App\Http\Requests\Admin\Dynasty\IndexDynasty;
use App\Http\Requests\Admin\Dynasty\StoreDynasty;
use App\Http\Requests\Admin\Dynasty\UpdateDynasty;
use App\Models\Dynasty;
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

class DynastyController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexDynasty $request
     * @return array|Factory|View
     */
    public function index(IndexDynasty $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Dynasty::class)->processRequestAndGet(
            // pass the request with params
            $request,

            // set columns to query
            ['id', 'f_id', 'name', 'name_lang'],

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

        return view('admin.dynasty.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.dynasty.create');

        return view('admin.dynasty.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDynasty $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreDynasty $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Dynasty
        $dynasty = Dynasty::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/dynasties'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/dynasties');
    }

    /**
     * Display the specified resource.
     *
     * @param Dynasty $dynasty
     * @throws AuthorizationException
     * @return void
     */
    public function show(Dynasty $dynasty)
    {
        $this->authorize('admin.dynasty.show', $dynasty);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Dynasty $dynasty
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Dynasty $dynasty)
    {
        $this->authorize('admin.dynasty.edit', $dynasty);


        return view('admin.dynasty.edit', [
            'dynasty' => $dynasty,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateDynasty $request
     * @param Dynasty $dynasty
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateDynasty $request, Dynasty $dynasty)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Dynasty
        $dynasty->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/dynasties'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/dynasties');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyDynasty $request
     * @param Dynasty $dynasty
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyDynasty $request, Dynasty $dynasty)
    {
        $dynasty->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyDynasty $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyDynasty $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    DB::table('dynasties')->whereIn('id', $bulkChunk)
                        ->update([
                            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
