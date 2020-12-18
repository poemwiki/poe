<?php
namespace App\Http\Controllers;

use App\Http\Requests\Admin\Author\UpdateAuthor;
use App\Models\Author;
use App\Models\Dynasty;
use App\Models\Nation;
use App\Models\Poem;
use App\Repositories\AuthorRepository;
use Illuminate\Routing\Redirector;


class AuthorController extends Controller {
    /** @var  AuthorRepository */
    private $authorRepository;

    public function __construct(AuthorRepository $poemRepo) {
        $this->middleware('auth')->except(['show', 'random']);
        $this->authorRepository = $poemRepo;
    }

    /**
     * Display the specified author
     * @param string $fakeId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($fakeId) {
        $id = Author::getIdFromFakeId($fakeId);
        $author = Author::findOrFail($id);
        $poemsAsPoet = Poem::where(['poet_id' => $id])->get();
        $poemsAsTranslator = Poem::where(['translator_id' => $id])->get();

        $from = request()->get('from');
        $fromPoetName = '';
        if (is_numeric($from)) {
            $fromPoem = Poem::findOrFail($from);
            if($fromPoem->poet_cn) {
                $fromPoetName = $fromPoem->poet_cn;
                if ($fromPoem->poet_cn !== $fromPoem->poet) {
                    $fromPoetName .= $fromPoem->poet;
                }
            } else {
                $fromPoetName = $fromPoem->poet;
            }
        }


        return view('authors.show')->with([
            'poetDesc' => $author->describe_lang,
            'poetName' => $author->name_lang,
            'author' => $author,
            'poemsAsPoet' => $poemsAsPoet,
            'poemsAsTranslator' => $poemsAsTranslator,
            'fromPoetName' => $fromPoetName
        ]);
    }


    /**
     * @param string $fakeId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function edit($fakeId) {
        $id = Author::getIdFromFakeId($fakeId);
        $author = Author::select(['id', 'name_lang', 'describe_lang'])->findOrFail($id);

        return view('authors.edit', [
            'author' => $author,
            'nationList' => Nation::select('name_lang', 'id')->get(),
            'dynastyList' => Dynasty::select('name_lang', 'id')->get(),
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param UpdateAuthor $request
     * @return array|RedirectResponse|Redirector
     */
    public function update($fakeId, UpdateAuthor $request) {
        // Sanitize input
        $sanitized = $request->getSanitized();

        $id = Author::getIdFromFakeId($fakeId);
        $this->authorRepository->update($sanitized, $id);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/authors'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/authors');
    }

}
