<?php
namespace App\Http\Controllers;

use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class PoetController extends AppBaseController {
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
    public function show($poetName) {
        // TODO after created Poet table and model, this should be changed to get poem from poet_id
        $poems = Poem::where(['poet' => $poetName])->orWhere(['poet_cn' => $poetName])->get();
        if(count($poems) <= 0){
            throw new ModelNotFoundException();
        }

        // get title from user input
        // get wikidata id from wikipedia api
        // https://en.wikipedia.org/w/api.php?action=query&prop=pageprops&format=json&formatversion=2&titles=Louise_Glück
        // get site url
        // https://www.wikidata.org/w/api.php?action=wbgetentities&ids=Q2344210&props=sitelinks
        // https://www.wikidata.org/w/api.php?action=wbgetentities&ids=Q2344210&props=sitelinks&sitefilter=zhwiki
        // https://en.wikipedia.org/wiki/Special:ApiSandbox#action=sitematrix&format=json&formatversion=2
        // get summary from wiki api
        // https://zh.wikipedia.org/api/rest_v1/page/summary/路易丝·格吕克

        $poetDesc = '';
        return view('poets.show')->with([
            'poetDesc' => $poetDesc,
            'poetName' => $poetName,
            'randomPoemUrl' => $this->poemRepository->randomOne()->url,
            'poems' => $poems
        ]);
    }

}
