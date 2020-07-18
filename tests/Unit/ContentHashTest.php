<?php

namespace Tests\Unit;

use App\Models\Poem;
use PHPUnit\Framework\TestCase;

class ContentHashTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testNoSpaceTest()
    {
        $this->assertEquals('', Poem::noSpace(''));
        $this->assertEquals('', Poem::noSpace(' '));
        $this->assertEquals('', Poem::noSpace('  '));
        $this->assertEquals('ssf', Poem::noSpace(" s\n
        sf"));
        $this->assertEquals('ssf', Poem::noSpace(" s　　sf "));
    }
}
