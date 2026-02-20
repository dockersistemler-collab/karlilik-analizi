<?php

namespace Tests\Unit;

use App\Support\TRNumberParser;
use PHPUnit\Framework\TestCase;

class TRNumberParserTest extends TestCase
{
    public function test_parse_tr_format_numbers(): void
    {
        $this->assertSame(1234.56, TRNumberParser::parse('1.234,56'));
        $this->assertSame(1.23, TRNumberParser::parse('1,23'));
        $this->assertSame(1234.0, TRNumberParser::parse('1.234'));
        $this->assertSame(12.5, TRNumberParser::parse('12.5'));
    }

    public function test_parse_returns_null_for_invalid(): void
    {
        $this->assertNull(TRNumberParser::parse('abc'));
        $this->assertNull(TRNumberParser::parse(''));
        $this->assertNull(TRNumberParser::parse(null));
    }
}
