<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
use Tests\TestCase;

class ContentHashTest extends TestCase {
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNoSpaceTest() {
        $this->assertEquals('', Str::noSpace(''));
        $this->assertEquals('', Str::noSpace(' '));
        $this->assertEquals('', Str::noSpace('  '));
        $this->assertEquals('ssf', Str::noSpace(" s\n
        sf"));
        $this->assertEquals('ssf', Str::noSpace(' s　　sf '));
    }
}
