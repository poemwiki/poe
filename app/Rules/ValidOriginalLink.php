<?php

namespace App\Rules;

use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Integer;

class ValidOriginalLink implements Rule {
    /**
     * @var int|null
     */
    private $poemId;

    private $changeToPoetId;
    private $reason;

    /**
     * Create a new rule instance.
     *
     * @param int|null $poem_id if of poem to change
     * @param string $transKey
     */
    public function __construct($poem_id, $changeToPoetid) {
        $this->poemId = $poem_id;
        $this->changeToPoetId = $changeToPoetid;
        $this->reason = '';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool false if failed
     */
    public function passes($attribute, $value) {
        if($value === '' or $value === null) {
            return true;
        }

        $pattern = '@^' . request()->getHost() . '/p/(.*)$@';
        $fakeId = Str::of($value)->match($pattern)->__toString();
        if ($fakeId) {
            $idFromFakeId = Poem::getIdFromFakeId($fakeId);
            if (!is_numeric($idFromFakeId)) {
                return false;
            }
            $translateFrom = Poem::find($idFromFakeId);
            if(!$translateFrom) {
                return false;
            }

            $hasPoetId = $translateFrom->poet_id && $this->changeToPoetId;
            if(!$hasPoetId) {
                // $this->reason = trans('error.Two poem should link to same poet');
                // return false;
                return true;
            }

            // TODO 原作译作任意一个关联了作者，以此作者为准，更改所有译作的作者
            if($translateFrom->poet_id !== $this->changeToPoetId) {
                $this->reason = trans('error.Two poem should belong to same poet');
                return false;
            }

            return $this->ringTestPass($translateFrom);
        }
        return false;
    }

    public function ringTestPass(Poem $translateFrom) {
        if($translateFrom->id === $this->poemId) {
            $this->reason = trans('error.Can not translated from self');
            return false;
        }

        // while ($translateFrom = $translateFrom->originalPoem) {
        //     if($translateFrom->id === $this->poemId) {
        //         $this->ringTestFailed = 2;
        //         return false;
        //     }
        //     dd('sss');
        //     if(!$translateFrom->is_translated) break;
        // }
        // return true;


        $ids = [$this->poemId];

        do {
            $ids[] = $translateFrom->id; // 保险起见，用笨办法防止环形链引起无限循环
            if(in_array($translateFrom->original_id, $ids)
                && $translateFrom->is_translated
            ) {
                $this->reason = trans('error.Can not construct a ring');
                return false;
            } else if(!$translateFrom->is_translated){
                return true;
            }
        } while ($translateFrom = $translateFrom->originalPoem);

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return trans('error.Not a valid poem url', [
            'reason' => $this->reason
        ]);
    }
}
