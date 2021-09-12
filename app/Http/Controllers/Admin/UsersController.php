<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Listing;
use App\Http\Requests\Admin\User\BulkDestroyUser;
use App\Http\Requests\Admin\User\DestroyUser;
use App\Http\Requests\Admin\User\IndexUser;
use App\Http\Requests\Admin\User\StoreUser;
use App\Http\Requests\Admin\User\UpdateUser;
use App\Models\UserBind;
use App\User;
use Brackets\AdminListing\Facades\AdminListing;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UsersController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @param IndexUser $request
     * @return array|Factory|View
     */
    public function index(IndexUser $request) {
        // create and AdminListing instance for a specific model and
        $data = Listing::create(User::class)->processRequestAndGet(
        // pass the request with params
            $request,

            // set columns to query
            [
                // 'users.id', 'name', 'email', 'email_verified_at', 'is_admin', 'updated_at', 'is_v', 'weight',
                // '`bind`.`id` as `bind_id`',
                // 'bind.id as bind_id', 'bind.bind_status as bind_status', 'bind.bind_ref as bind_ref', 'bind.nickname as bind_name', 'bind.gender as bind_gender'
            ],

            // set columns to searchIn
            ['email', 'users.id', 'name', 'bind.nickname'],

            function (Builder $query) use ($request) {
                $query->select(DB::raw(
                    'GROUP_CONCAT(`bind`.`id`) as `bind_ids`,
                    users.id, users.name, users.avatar, users.email, users.email_verified_at, users.is_admin, users.updated_at, users.created_at, is_v, weight'
                ));
                if (!$request->input('orderBy')) {
                    $query->orderBy('users.updated_at', 'desc');
                }

                $query->leftJoin('user_bind_info as bind', 'users.id', '=', 'bind.user_id')
                    ->groupBy('users.id');
            }
        );

        $binds = $data->reduce(function ($carry, $item) {
            if (!$item['bind_ids']) {
                return $carry;
            }

            return $item['bind_ids'] . ',' . $carry;
        });
        $bindIDs   = explode(',', trim($binds, ','));
        $userBinds = UserBind::findMany($bindIDs)->keyBy('id')
            ->map->only(['id', 'bind_status', 'bind_ref', 'nickname', 'gender', 'avatar'])->toArray();

        // TODO aggregate bind_ids for every user instead of showing multiple lines for each bind of user
        // $users = collect([]);
        foreach ($data as &$userWithBinds) {
            $userWithBinds['binds'] = $userWithBinds['bind_ids']
                ? collect(explode(',', $userWithBinds['bind_ids']))->map(function ($id) use ($userBinds) {
                    return $userBinds[$id];
                })
                : [];
            $userWithBinds['avatar'] = $userWithBinds->avatarUrl;
        }

        if ($request->ajax()) {
            return ['data' => $data];
        }

        return view('admin.user.index', ['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function create() {
        $this->authorize('admin.user.create');

        return view('admin.user.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreUser $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreUser $request) {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the User
        $user = User::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/users'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/users');
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public function show(User $user) {
        $this->authorize('admin.user.show', $user);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param User $user
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function edit(User $user) {
        $this->authorize('admin.user.edit', $user);

        return view('admin.user.edit', [
            'user' => $user,
        ]);
    }

    public function addV(User $user) {
        $this->authorize('admin.user.edit', $user);

        return view('admin.user.addV', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUser $request
     * @param User       $user
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateUser $request, User $user) {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values User
        $user->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/users'),
                'message'  => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/users');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyUser $request
     * @param User        $user
     * @return ResponseFactory|RedirectResponse|Response
     * @throws Exception
     */
    public function destroy(DestroyUser $request, User $user) {
        $user->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyUser $request
     * @return Response|bool
     * @throws Exception
     */
    public function bulkDestroy(BulkDestroyUser $request): Response {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    User::whereIn('id', $bulkChunk)->delete();

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }
}
