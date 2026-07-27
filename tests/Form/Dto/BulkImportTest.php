<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Form\Dto;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport;

class BulkImportTest extends TestCase
{
    public function testDataDefaultsToEmptyStringInsteadOfThrowing(): void
    {
        $this->assertSame('', new BulkImport()->getData());
    }

    public function testModeDefaultsToCreateOrUpdate(): void
    {
        $this->assertSame(BulkImport::MODE_CREATE_OR_UPDATE, new BulkImport()->getMode());
    }

    public function testSettersAreFluent(): void
    {
        $item = new BulkImport();

        $this->assertSame($item, $item->setData("a\tb\n1\t2"));
        $this->assertSame($item, $item->setMode(BulkImport::MODE_CREATE_ONLY));
        $this->assertSame(BulkImport::MODE_CREATE_ONLY, $item->getMode());
    }

    public function testGetRowsParsesTsvUsingTheFirstLineAsHeaders(): void
    {
        $item = new BulkImport()->setData("domain\tip\nexample.test\t10.0.0.1\nother.test\t10.0.0.2");

        $this->assertSame([
            ['domain' => 'example.test', 'ip' => '10.0.0.1'],
            ['domain' => 'other.test', 'ip' => '10.0.0.2'],
        ], $item->getRows());
    }

    public function testGetRowsLowercasesAndTrimsHeaderKeys(): void
    {
        $item = new BulkImport()->setData("  Domain  \t IP \nexample.test\t10.0.0.1");

        $this->assertSame([['domain' => 'example.test', 'ip' => '10.0.0.1']], $item->getRows());
    }

    public function testGetRowsSkipsBlankLines(): void
    {
        $item = new BulkImport()->setData("domain\nexample.test\n\nother.test\n");

        $this->assertSame([['domain' => 'example.test'], ['domain' => 'other.test']], $item->getRows());
    }

    public function testGetRowsReturnsEmptyArrayWhenOnlyHeadersArePresent(): void
    {
        $item = new BulkImport()->setData("domain\tip");

        $this->assertSame([], $item->getRows());
    }
}
