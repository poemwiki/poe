<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {
    public static $lorem1 = <<<'TEXT'
Lorem ipsum dolor sit amet, consectetur adipiscing elit.

Vestibulum euismod, nisl eget consectetur sagittis,

dolor nunc egestas erat, eu consectetur nunc nisi

non nunc. Nullam eget nisl eu nisi porta consectetur.
TEXT;
    public static $lorem1Cleaned = <<<'TEXT'
Lorem ipsum dolor sit amet, consectetur adipiscing elit.
Vestibulum euismod, nisl eget consectetur sagittis,
dolor nunc egestas erat, eu consectetur nunc nisi
non nunc. Nullam eget nisl eu nisi porta consectetur.
TEXT;
    public static $lorem2 = <<<'TEXT'
Lorem ipsum dolor sit amet, consectetur adipiscing elit.

  Vestibulum euismod, nisl eget consectetur sagittis,

  dolor nunc egestas erat, eu consectetur nunc nisi

  non nunc. Nullam eget nisl eu nisi porta consectetur.

  eget nisl eu nisi porta consec
TEXT;
    public static $lorem2Cleaned = <<<'TEXT'
Lorem ipsum dolor sit amet, consectetur adipiscing elit.
Vestibulum euismod, nisl eget consectetur sagittis,
dolor nunc egestas erat, eu consectetur nunc nisi
non nunc. Nullam eget nisl eu nisi porta consectetur.
eget nisl eu nisi porta consec
TEXT;
    public static $lorem3 = <<<'TEXT'
Lorem ipsum dolor sit amet, consectetur adipiscing elit.

Vestibulum euismod, nisl eget consectetur sagittis,

dolor nunc egestas erat, eu consectetur nunc nisi

non nunc. Nullam eget nisl eu nisi porta consectetur.

eget nisl eu nisi porta consec
TEXT;

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testLineStat() {
        list($textLineCount, $avgTextLength, $emptyLineCount, $spaceStartLineCount) = getLineStat(self::$lorem1);
        $this->assertEquals(4, $textLineCount);
        $this->assertEquals((56 + 51 + 49 + 53) / 4, $avgTextLength);
        $this->assertEquals(3, $emptyLineCount);
        $this->assertEquals(0, $spaceStartLineCount);

        list($textLineCount, $avgTextLength, $emptyLineCount, $spaceStartLineCount) = getLineStat(self::$lorem2);
        $this->assertEquals(5, $textLineCount);
        $this->assertEquals((56 + 51 + 49 + 53 + 30) / 5, $avgTextLength);
        $this->assertEquals(4, $emptyLineCount);
        $this->assertEquals(4, $spaceStartLineCount);
    }

    public function testTextClean() {
        $text = textClean(self::$lorem1);
        $this->assertEquals(self::$lorem1Cleaned, $text);
        $text = textClean(self::$lorem2);
        $this->assertEquals(self::$lorem2Cleaned, $text);
        $text = textClean(self::$lorem2, 0);
        $this->assertEquals(self::$lorem3, $text);
    }
}
