<?php

namespace App\Http\Livewire;

use App\Models\Poem;
use App\Models\Review;
use App\Repositories\ReviewRepository;
use App\Repositories\ScoreRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ShowReviews extends Component {
    use AuthorizesRequests;

    /** @var  ReviewRepository */
    private $reviewRepository;
    /** @var  ScoreRepository */
    private $scoreRepository;
    public $poem;
    public $title;
    public $content;
    public $isEditing = false;

    //    protected $listeners = ['new-review' => '$refresh'];


    protected $rules = [
        'content' => 'required|string|min:10|max:8000',
        'title' => 'nullable|string|max:40',
    ];

    public function __construct() {
        $this->reviewRepository = new ReviewRepository(app());
        $this->scoreRepository = new ScoreRepository(app());
        parent::__construct();
    }

    public function updated($propName) {
        $this->isEditing = true;
        $this->validateOnly($propName);
        $this->dispatchBrowserEvent('review-updated');
    }

    public function submit() {
        $this->isEditing = true;
        $this->validate();
        $this->dispatchBrowserEvent('review-updated');
        // Execution doesn't reach here if validation fails.
        Review::create([
            'title' => $this->title,
            'content' => $this->content,
            'poem_id' => $this->poem->id,
            'user_id' => Auth::user()->id
        ]);
        $this->isEditing = false;
    }

    public function delete($review_id) {
        // TODO fix this: review-modal will be open after delete if not setting isEditing=false
        $this->isEditing = false;
        $review = Review::findOrFail($review_id);
        if ($review->user_id !== Auth::user()->id) {
            return;
        }
        Review::destroy($review_id);
    }

    public function mount(Poem $poem) {
        $this->poem = $poem;
    }

    public function render() {
        // TODO paginate for reviews
        $reviews = $this->reviewRepository->listByOriginalPoem($this->poem, 100);
        $userIds = [];
        $poemIds = [];
        foreach ($reviews as $review) {
            $userIds[] = $review->user->id;
            $poemIds[] = $review->poem->id;
        }

        $userScore = [];
        $scores = $this->scoreRepository->listByPoemsUsers($poemIds, $userIds)->get();
        foreach ($scores as $score) {
            $userScore[$score->user_id] = $score->score;
        }


        return view('livewire.show-reviews', [
            'reviews' => $reviews,
            'userScore' => $userScore
        ]);
    }
}
