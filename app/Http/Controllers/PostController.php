<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\Poem\IndexPoem;
use App\Http\Requests\CreatePoemRequest;
use App\Http\Requests\UpdatePoemRequest;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use App\Http\Controllers\AppBaseController;
use Brackets\AdminListing\Facades\AdminListing;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Response;
use Flash;

class PostController extends AppBaseController
{
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepo) {
        $this->poemRepository = $poemRepo;
    }



    /**
     * Display a listing of the resource.
     * @TODO this should be a search page
     * @param IndexPoem $request
     * @return array|Factory|View
     */
    public function index(IndexPoem $request) {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Poem::class)->processRequestAndGet(
        // pass the request with params
            $request,

            // set columns to query
            ['id', 'title', 'language', 'is_original', 'poet', 'poet_cn', 'bedtime_post_id', 'bedtime_post_title', 'length', 'translator', 'from', 'year', 'month', 'date', 'dynasty', 'nation', 'need_confirm', 'is_lock', 'content_id'],

            // set columns to searchIn
            ['id', 'title', 'poet', 'poet_cn', 'bedtime_post_title', 'poem', 'translator', 'from', 'year', 'month', 'date', 'dynasty', 'nation']
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



    public function edit() {

    }
}
