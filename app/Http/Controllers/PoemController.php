<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePoemRequest;
use App\Http\Requests\UpdatePoemRequest;
use App\Models\Language;
use App\Repositories\PoemRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class PoemController extends AppBaseController
{
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepo) {
        $this->middleware('auth');
        $this->poemRepository = $poemRepo;
    }

    /**
     * Display a listing of the Poem.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $poems = $this->poemRepository->listAll(15, 'updated_at', 'desc',
            ['id', 'title', 'poet', 'poet_cn', 'length', 'translator', 'dynasty', 'nation', 'language', 'is_original', 'need_confirm']);

        return view('poems.index')
            ->with('poems', $poems);
    }

    /**
     * Show the form for creating a new Poem.
     *
     * @return Response
     */
    public function create()
    {
        return view('poems.create')
            ->with('langList', Language::listAll());
    }

    /**
     * Store a newly created Poem in storage.
     *
     * @param CreatePoemRequest $request
     *
     * @return Response
     */
    public function store(CreatePoemRequest $request)
    {
        $input = $request->all();

        $poem = $this->poemRepository->create($input);

        Flash::success('Poem saved successfully.');

        return redirect(route('poems.index'));
    }

    /**
     * Display the specified Poem.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $poem = $this->poemRepository->find($id);

        if (empty($poem)) {
            Flash::error('Poem not found');

            return redirect(route('poems.index'));
        }

        return view('poems.show')->with('poem', $poem)
            ->with('langList', Language::listAll());
    }

    /**
     * Show the form for editing the specified Poem.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $poem = $this->poemRepository->find($id);

        if (empty($poem)) {
            Flash::error('Poem not found');

            return redirect(route('poems.index'));
        }

        return view('poems.edit')->with('poem', $poem)
          ->with('langList', Language::listAll());
    }

    /**
     * Update the specified Poem in storage.
     *
     * @param int $id
     * @param UpdatePoemRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePoemRequest $request)
    {
        $poem = $this->poemRepository->find($id);

        if (empty($poem)) {
            Flash::error('Poem not found');

            return redirect(route('poems.index'));
        }

        $poem = $this->poemRepository->update($request->all(), $id);

        Flash::success('Poem updated successfully.');

        return redirect(route('poems.index'));
    }

    /**
     * Remove the specified Poem from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $poem = $this->poemRepository->find($id);

        if (empty($poem)) {
            Flash::error('Poem not found');

            return redirect(route('poems.index'));
        }

        $this->poemRepository->delete($id);

        Flash::success('Poem deleted successfully.');

        return redirect(route('poems.index'));
    }
}
