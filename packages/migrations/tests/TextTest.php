<?php

namespace App\Tests\Helper;

use App\Helper\Text;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Text::class)]
class TextTest extends TestCase
{

    #Cover
    public function testFormatBytes()
    {
        $this->assertEquals('10KB', Text::formatBytes(10 * 1024));
        $this->assertEquals('10MB', Text::formatBytes(10 * 1024 ** 2));
        $this->assertEquals('9.555GB', Text::formatBytes(9.555 * 1024 ** 3, 3));
    }
}
