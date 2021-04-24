<?php

namespace App\Http\Livewire;

use App\Models\Poem;
use App\Models\Score as ScoreModel;
use App\Repositories\ScoreRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Score extends Component {
    use AuthorizesRequests;

    /** @var  ScoreRepository */
    private $scoreRepository;
    public $poem;
    public $rating = null;

    protected $rules = [
    ];

    public function __construct() {
        $this->scoreRepository = new ScoreRepository(app());
        $allowedScores = collect(ScoreModel::$SCORE);
        $this->sortedScores = $allowedScores->sort()->values()->all();
        $this->descSortedScores = $allowedScores->sortDesc()->values()->all();
        $this->rules = [
            'rating' => Rule::in(ScoreModel::$SCORE)
        ];
        parent::__construct();
    }

    public function mount(Poem $poem) {
        $this->poem = $poem;
        $this->score = $this->scoreRepository->calcScoreByPoem($this->poem);
        if (Auth::check()) {
            $record = \App\Models\Score::select('score')->where([
                'poem_id' => $this->poem->id,
                'user_id' => Auth::user()->id
            ])->first();
            $this->rating = $record ? $record->score : null;
        } else {
            $this->rating = null;
        }
    }

    public function updatedRating($value) {
        if(!Auth::check()) {
            return redirect(route('login', ['ref' => route('poems/show', $this->poem->fake_id)]));
        }

        $this->validateOnly('rating');

        // TODO 同 API 路由下的打分逻辑应放在一处
        $isMaster = false;
        $user = Auth::user();
        $tags = $this->poem->tags;
        if($tags->count() && $tags[0] && $tags[0]->campaign) {
            /** @var \App\Models\Campaign $campaign */
            $campaign = $tags[0]->campaign;
            $isMaster = $campaign->isMaster($user->id);
        }
        $weight = $user->weight;
        return  $this->scoreRepository->updateOrCreate(
            ['poem_id' => $this->poem->id, 'user_id' => $user->id],
            [
                'score' => $this->rating,
                'weight' => $isMaster ? max(100, $weight) : $weight
            ]
        );
    }

    public function remove() {
        \Illuminate\Support\Facades\Log::info('score removed: poem_id=' . $this->poem->id);
        $res = \App\Models\Score::where(['poem_id' => $this->poem->id, 'user_id' => Auth::user()->id])->first();
        if ($res) {
            $res->delete();
            $this->rating = null;
        }
    }

    public function render() {
        return view('livewire.score', [
            'score' => $this->poem->scoreArray,
            'sortedScores' => $this->sortedScores,
            'descSortedScores' => $this->descSortedScores
        ]);
    }
}
