<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nation\BulkDestroyNation;
use App\Http\Requests\Admin\Nation\DestroyNation;
use App\Http\Requests\Admin\Nation\IndexNation;
use App\Http\Requests\Admin\Nation\StoreNation;
use App\Http\Requests\Admin\Nation\UpdateNation;
use App\Models\Nation;
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

class NationController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexNation $request
     * @return array|Factory|View
     */
    public function index(IndexNation $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Nation::class)->processRequestAndGet(
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

        return view('admin.nation.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create()
    {
        $this->authorize('admin.nation.create');

        return view('admin.nation.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreNation $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreNation $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Nation
        $nation = Nation::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/nations'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/nations');
    }

    /**
     * Display the specified resource.
     *
     * @param Nation $nation
     * @throws AuthorizationException
     * @return void
     */
    public function show(Nation $nation)
    {
        $this->authorize('admin.nation.show', $nation);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Nation $nation
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit(Nation $nation)
    {
        $this->authorize('admin.nation.edit', $nation);


        return view('admin.nation.edit', [
            'nation' => $nation,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateNation $request
     * @param Nation $nation
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateNation $request, Nation $nation)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Nation
        $nation->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/nations'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/nations');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyNation $request
     * @param Nation $nation
     * @throws Exception
     * @return ResponseFactory|RedirectResponse|Response
     */
    public function destroy(DestroyNation $request, Nation $nation)
    {
        $nation->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyNation $request
     * @throws Exception
     * @return Response|bool
     */
    public function bulkDestroy(BulkDestroyNation $request) : Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    DB::table('nations')->whereIn('id', $bulkChunk)
                        ->update([
                            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s')
                    ]);

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
