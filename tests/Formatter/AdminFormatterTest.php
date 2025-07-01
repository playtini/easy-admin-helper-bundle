<?php

namespace Formatter;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Formatter\AdminFormatter;

class AdminFormatterTest extends TestCase
{
    public function testFormatBoolNull(): void
    {
        $this->assertEquals('1', AdminFormatter::formatBoolNull(true));
        $this->assertEquals('0', AdminFormatter::formatBoolNull(false));
        $this->assertEquals('-', AdminFormatter::formatBoolNull(null));
    }

    public function testFormatUrlPath(): void
    {
        $this->assertEquals('path', AdminFormatter::formatUrlPath('https://example.com/path'));
        $this->assertEquals('invalid_url: (null)', AdminFormatter::formatUrlPath(null));
        $this->assertEquals('invalid_url: inva...', AdminFormatter::formatUrlPath('invalid_url'));
        $this->assertEquals('invalid_url: /path', AdminFormatter::formatUrlPath('/path'));
        $this->assertEquals('very/lo...', AdminFormatter::formatUrlPath('https://example.com/very/long/path/to/resource', 10));
    }
}
