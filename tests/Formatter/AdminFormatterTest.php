<?php

namespace Formatter;

use DateTimeImmutable;
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

    public function testFormatUrlPathRootUrl(): void
    {
        $this->assertEquals('/', AdminFormatter::formatUrlPath('https://example.com/'));
        $this->assertEquals('/', AdminFormatter::formatUrlPath('https://example.com'));
    }

    public function testBreakWords(): void
    {
        $this->assertEquals('<span class="break-words">hello</span>', AdminFormatter::breakWords('hello'));
        $this->assertEquals('<span class="break-words"></span>', AdminFormatter::breakWords(null));
        $this->assertEquals('<span class="break-words">&lt;b&gt;xss&lt;/b&gt;</span>', AdminFormatter::breakWords('<b>xss</b>'));
    }

    public function testMuteZero(): void
    {
        $this->assertStringContainsString('text-muted', AdminFormatter::muteZero(0));
        $this->assertStringContainsString('0', AdminFormatter::muteZero(0));
        $this->assertEquals('42', AdminFormatter::muteZero(42));
        $this->assertEquals('1,000', AdminFormatter::muteZero(1000));
    }

    public function testFormatExpireDateNull(): void
    {
        $this->assertEquals('-', AdminFormatter::formatExpireDate(null));
    }

    public function testFormatExpireDateDanger(): void
    {
        $date = new DateTimeImmutable('-1 day');
        $result = AdminFormatter::formatExpireDate($date);
        $this->assertStringContainsString('text-danger', $result);
    }

    public function testFormatExpireDateWarning(): void
    {
        $date = new DateTimeImmutable('+3 days');
        $result = AdminFormatter::formatExpireDate($date);
        $this->assertStringContainsString('text-warning', $result);
    }

    public function testFormatExpireDateInfo(): void
    {
        $date = new DateTimeImmutable('+15 days');
        $result = AdminFormatter::formatExpireDate($date);
        $this->assertStringContainsString('text-info', $result);
    }

    public function testFormatExpireDateDefault(): void
    {
        $date = new DateTimeImmutable('+60 days');
        $result = AdminFormatter::formatExpireDate($date);
        $this->assertStringNotContainsString('text-danger', $result);
        $this->assertStringNotContainsString('text-warning', $result);
        $this->assertStringNotContainsString('text-info', $result);
    }

    public function testFormatBoolNullEmoji(): void
    {
        $this->assertEquals('🟢', AdminFormatter::formatBoolNullEmoji(true));
        $this->assertEquals('🔴', AdminFormatter::formatBoolNullEmoji(false));
        $this->assertEquals(' ', AdminFormatter::formatBoolNullEmoji(null));
    }

    public function testFormatHttpStatus(): void
    {
        $this->assertStringContainsString('bg-warning', AdminFormatter::formatHttpStatus(100));
        $this->assertStringContainsString('bg-success', AdminFormatter::formatHttpStatus(200));
        $this->assertStringContainsString('bg-warning', AdminFormatter::formatHttpStatus(301));
        $this->assertStringContainsString('bg-danger', AdminFormatter::formatHttpStatus(404));
        $this->assertStringContainsString('bg-danger', AdminFormatter::formatHttpStatus(500));
    }

    public function testPercents(): void
    {
        $this->assertStringContainsString('-', AdminFormatter::percents(0, 0));
        $this->assertStringContainsString('0%', AdminFormatter::percents(0, 100));
        $this->assertEquals('50%', AdminFormatter::percents(50, 100));
        $this->assertEquals('33.3%', AdminFormatter::percents(1, 3, 1));
    }
}
