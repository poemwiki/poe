<?php

namespace Tests\Unit;

use App\Models\Poem;
use Tests\TestCase;

class PoemModelTest extends TestCase
{
    public function test_dateStr_attribute_with_full_date()
    {
        $poem = new Poem([
            'year' => 2023,
            'month' => 12,
            'date' => 25
        ]);

        $this->assertEquals('2023.12.25', $poem->dateStr);
    }

    public function test_dateStr_attribute_with_year_and_month()
    {
        $poem = new Poem([
            'year' => 2023,
            'month' => 12
        ]);

        $this->assertEquals('2023.12', $poem->dateStr);
    }

    public function test_dateStr_attribute_with_month_and_date()
    {
        $poem = new Poem([
            'month' => 12,
            'date' => 25
        ]);

        $this->assertEquals('12.25', $poem->dateStr);
    }

    public function test_dateStr_attribute_with_year_only()
    {
        $poem = new Poem([
            'year' => 2023
        ]);

        $this->assertEquals('2023', $poem->dateStr);
    }

    public function test_dateStr_attribute_with_no_date_info()
    {
        $poem = new Poem();

        $this->assertNull($poem->dateStr);
    }

    public function test_dateStr_attribute_with_month_only()
    {
        $poem = new Poem([
            'month' => 12
        ]);

        $this->assertNull($poem->dateStr);
    }

    public function test_dateStr_attribute_with_date_only()
    {
        $poem = new Poem([
            'date' => 25
        ]);

        $this->assertNull($poem->dateStr);
    }
}
