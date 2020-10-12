<?php

namespace App\Http\Livewire;

use App\Models\Poem;
use App\Repositories\ScoreRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Score extends Component {
    /** @var  ScoreRepository */
    private $scoreRepository;
    public $poem;
    public $rating = null;

    public function __construct() {
//        $this->middleware('auth')->except(['show', 'showContributions']);
        $this->scoreRepository = new ScoreRepository(app());
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

    public function updatingRating($value) {
        $rating = \App\Models\Score::updateOrCreate(
            ['user_id' => Auth::user()->id, 'poem_id' => $this->poem->id],
            ['score' => $value]
        );
    }

    public function render() {
        return view('livewire.score', [
            'score' => $this->scoreRepository->calcScoreByPoem($this->poem)
        ]);
    }
}
