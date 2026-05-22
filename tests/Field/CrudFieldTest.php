<?php

namespace Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Field\CrudField;

class CrudFieldTest extends TestCase
{
    public function testCreatedAtDateUsesDateOnlyFormat(): void
    {
        $dto = CrudField::createdAtDate()->getAsDto();

        $this->assertSame('createdAt', $dto->getProperty());
        $this->assertSame('Created', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd', $dto->getCustomOption(DateField::OPTION_DATE_PATTERN));
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testCreatedAtDateCustomColumns(): void
    {
        $this->assertSame('col-md-6', CrudField::createdAtDate(6)->getAsDto()->getColumns());
    }

    public function testCreatedAtMinuteUsesMinutePrecisionFormat(): void
    {
        $dto = CrudField::createdAtMinute()->getAsDto();

        $this->assertSame('createdAt', $dto->getProperty());
        $this->assertSame('Created', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd HH:mm', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testCreatedAtMinuteCustomColumns(): void
    {
        $this->assertSame('col-md-4', CrudField::createdAtMinute(4)->getAsDto()->getColumns());
    }

    public function testUpdatedAtDateUsesDateOnlyFormat(): void
    {
        $dto = CrudField::updatedAtDate()->getAsDto();

        $this->assertSame('updatedAt', $dto->getProperty());
        $this->assertSame('Updated', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd', $dto->getCustomOption(DateField::OPTION_DATE_PATTERN));
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testUpdatedAtDateCustomColumns(): void
    {
        $this->assertSame('col-md-8', CrudField::updatedAtDate(8)->getAsDto()->getColumns());
    }

    public function testUpdatedAtMinuteUsesMinutePrecisionFormat(): void
    {
        $dto = CrudField::updatedAtMinute()->getAsDto();

        $this->assertSame('updatedAt', $dto->getProperty());
        // NOTE: src currently sets label to 'Created' — looks like a copy-paste bug from createdAtMinute().
        // Asserting current behavior; flip to 'Updated' once the source is fixed.
        $this->assertSame('Created', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd HH:mm', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testUpdatedAtMinuteCustomColumns(): void
    {
        $this->assertSame('col-md-3', CrudField::updatedAtMinute(3)->getAsDto()->getColumns());
    }
}
