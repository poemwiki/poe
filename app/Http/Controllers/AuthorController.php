<?php
namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class AuthorController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepo) {
        $this->middleware('auth')->except(['show', 'random']);
        $this->poemRepository = $poemRepo;
    }

    /**
     * Display the specified Poet
     *
     * @param int $fakeId
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($id) {
        $poemsAsPoet = Poem::where(['poet_id' => $id])->get();
        $poemsAsTranslator = Poem::where(['translator_id' => $id])->get();
        $author = Author::findOrFail($id);

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
            'poemsAsPoet' => $poemsAsPoet,
            'poemsAsTranslator' => $poemsAsTranslator,
            'fromPoetName' => $fromPoetName
        ]);
    }

}
