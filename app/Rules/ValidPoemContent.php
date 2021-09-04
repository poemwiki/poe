<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidPoemContent implements Rule {
    /**
     * @var string
     */
    public $reason           = '';
    public $strictLineNum    = 0;
    public $maxTextLine      = 400;
    public $noExtraEmptyLine = true;

    /**
     * Create a new rule instance.
     *
     * @param int  $strictLineNum
     * @param int  $maxTextLine
     * @param bool $noExtraEmptyLine
     */
    public function __construct($strictLineNum = 0, $noExtraEmptyLine = true, $maxTextLine = 400) {
        $this->strictLineNum    = $strictLineNum;
        $this->noExtraEmptyLine = $noExtraEmptyLine;
        $this->maxTextLine      = $maxTextLine;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     * @return bool false if failed
     */
    public function passes($attribute, $value): bool {
        $arr            = explode("\n", $value);
        $lineCount      = count($arr);
        $emptyLineCount = 0;
        foreach ($arr as $line) {
            if (preg_match('#^[\s]*$#u', $line)) {
                ++$emptyLineCount;
            }
        }
        $textLineCount  = $lineCount - $emptyLineCount;

        if ($emptyLineCount >= ($lineCount - 1) / 2 && $this->noExtraEmptyLine) {
            $this->reason = '请删除多余空行。';

            return false;
        }

        if ($this->strictLineNum && $this->strictLineNum !== $textLineCount) {
            $this->reason = "行数限定{$this->strictLineNum}行";

            return false;
        }

        if ($this->maxTextLine && $this->maxTextLine < $textLineCount) {
            $this->reason = "行数超过限制，最多{$this->maxTextLine}行";

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return trans('error.Not a valid poem content', [
            'reason' => $this->reason
        ]);
    }
}
