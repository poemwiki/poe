<?php
namespace App\Http\Controllers;


use App\Models\Score;
use App\Repositories\PoemRepository;


class ScoreController extends Controller
{
    /** @var  ScoreRepository */
    private $scoreRepository;

    public function __construct(ScoreRepository $scoreRepo) {
        $this->scoreRepository = $scoreRepo;
    }


}
