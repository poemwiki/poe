<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Livewire\Score;
use App\Http\Requests\CreatePoemRequest;
use App\Models\Campaign;
use App\Models\Poem;
use App\Models\Tag;
use App\Repositories\PoemRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ScoreRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */
class PoemAPIController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;
    /** @var  ReviewRepository */
    private $reviewRepository;

    public function __construct(PoemRepository $poemRepository, ReviewRepository $reviewRepository) {
        $this->poemRepository = $poemRepository;
        $this->reviewRepository = $reviewRepository;
    }

    public function index(Request $request) {
        // TODO pagination
        // TODO order by
        $tagId = $request->input('tagId');
        $orderBy = $request->input('orderBy', 'created_at');
        if(is_numeric($tagId)) {
            return $this->responseSuccess($this->poemRepository->getByTagId($tagId, $orderBy)->toArray());
        }
    }

    public function mine(Request $request) {
        return $this->responseSuccess($this->poemRepository->getByOwner($request->user()->id));
    }

    public function relatedAll(Request $request) {
        return $this->responseSuccess($this->poemRepository->getRelated($request->user()->id));
    }

    /**
     * get related poem which has related campaign
     * @param Request $request
     * @return array
     */
    public function related(Request $request) {
        return $this->responseSuccess($this->poemRepository->getRelated($request->user()->id, true));
    }

    public function detail($id) {
        /** @var Poem $item */
        $item = Poem::find($id);
        $res = $item->toArray();
        $res['poet_image'] = $item->uploader->avatarUrl;
        $res['score_weight'] = round(ScoreRepository::calcWeight($id));
        // dd(($res['score_weight']));
        $res['reviews'] = $this->reviewRepository->listByOriginalPoem($item)->get()->map(function ($item) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            return $item;
        });
        $res['related'] = $this->poemRepository->random(2)
            ->where('id', '<>', $id)
            ->whereHas('tags', function ($query) use ($item) {
                $query->where('tag_id', '=', $item->tags[0]->id);
            })->get()->toArray();

        return $this->responseSuccess($res);
    }

    public function store(CreatePoemRequest $request) {
        $sanitized = $request->getSanitized();

        $poem = Poem::create($sanitized);
        $poem->tags()->save(Tag::find($sanitized['tag_id']));

        return $this->responseSuccess();
    }

    public function delete($poemId) {
        try {
            $this->authorize('api.poem.delete', Poem::find($poemId));
        } catch (AuthorizationException $e) {
            return $this->responseFail();
        }

        $poem = Poem::find($poemId);
        $poem->delete();

        return $this->responseSuccess();
    }

    public function share($poemId) {
        // just for test
        // if(config('app.env') == 'local' && !isset($_GET['force'])) {
        //     return $this->responseSuccess(['url' => 'http://pwiki.lol/poem-card/1.png']);
        // }

        // TODO check if $poem->image exists, if not then generate image
        $poem = Poem::find($poemId);

        $postData = ['compositionId' => 'pure', 'poem' => $poem->poem, 'title' => $poem->title];
        if(isset($_GET['force'])) {
            $postData['force'] = 1;
        }

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => "POST",
                'content' => http_build_query($postData),
                'timeout' => 30,
            ),
        );
        $context = stream_context_create($options);
        $img = file_get_contents("http://localhost:8888", false, $context);

        $dir = storage_path('app/public/poem-card/' . $poem->id);
        if(!is_dir($dir)) {
            mkdir($dir);
        }
        $storeDir = $dir .'/element-0.png';
        if(file_put_contents($storeDir, $img)) {
            return $this->responseSuccess(['url' => route('poem-card', $poemId)]);
        }

        return $this->responseFail();
    }
}
