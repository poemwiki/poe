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
    /**
     * @var bool
     */
    private $ringTestFailed;
    /**
     * @var int
     */
    private $poetTestFailed;
    private $changeToPoetId;

    /**
     * Create a new rule instance.
     *
     * @param int|null $poem_id if of poem to change
     * @param string $transKey
     */
    public function __construct($poem_id, $changeToPoetid) {
        $this->poemId = $poem_id;
        $this->changeToPoetId = $changeToPoetid;
        $this->ringTestFailed = 0;
        $this->poetTestFailed = 0;
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

        $pattern = '@^' . str_replace('.', '\.', config('app.url')) . '/p/(.*)$@';
        $fakeId = Str::of($value)->match($pattern)->__toString();
        if ($fakeId) {
            $translateFrom = Poem::find(Poem::getIdFromFakeId($fakeId));

            $hasPoetId = $translateFrom->poet_id && $this->changeToPoetId;
            if(!$hasPoetId) {
                $this->poetTestFailed = 1;
                return false;
            }
            if($translateFrom->poet_id !== $this->changeToPoetId) {
                $this->poetTestFailed = 2;
                return false;
            }

            return $this->ringTestPass($translateFrom);
        }
        return false;
    }

    public function ringTestPass(Poem $translateFrom) {
        if($translateFrom->id === $this->poemId) {
            $this->ringTestFailed = 1;
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
        if($this->poetTestFailed == 1) {
            return trans('error.Not a valid poem url', [
                'reason' => trans('error.Two poem should link to same poet')
            ]);
        }
        if($this->poetTestFailed == 2) {
            return trans('error.Not a valid poem url', [
                'reason' => trans('error.Two poem should belong to same poet')
            ]);
        }
        if($this->ringTestFailed === 1) {
            return trans('error.Not a valid poem url', [
                'reason' => trans('error.Can not translated from self')
            ]);
        }
        if($this->ringTestFailed === 2) {
            return trans('error.Not a valid poem url', [
                'reason' => trans('error.Can not construct a ring')
            ]);
        }
        return trans('error.Not a valid poem url', [
            'reason' => ''
        ]);
    }
}
