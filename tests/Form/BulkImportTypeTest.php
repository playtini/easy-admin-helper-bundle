<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Form;

use Playtini\EasyAdminHelperBundle\Form\BulkImportType;
use Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport;
use Symfony\Component\Form\Test\TypeTestCase;

class BulkImportTypeTest extends TypeTestCase
{
    public function testBuildsDataModeAndSaveChildren(): void
    {
        $form = $this->factory->create(BulkImportType::class);

        $this->assertTrue($form->has('data'));
        $this->assertTrue($form->has('mode'));
        $this->assertTrue($form->has('save'));
    }

    public function testIsBoundToTheBulkImportDto(): void
    {
        $form = $this->factory->create(BulkImportType::class);

        $this->assertSame(BulkImport::class, $form->getConfig()->getDataClass());
    }

    public function testOffersTheFiveImportModes(): void
    {
        $form = $this->factory->create(BulkImportType::class);

        $this->assertSame([
            'Create or update' => BulkImport::MODE_CREATE_OR_UPDATE,
            'Create only (error if exists)' => BulkImport::MODE_CREATE_ONLY,
            'Update only (error if missing)' => BulkImport::MODE_UPDATE_ONLY,
            'Create and skip existing' => BulkImport::MODE_CREATE_SKIP_EXISTING,
            'Update and skip missing' => BulkImport::MODE_UPDATE_SKIP_MISSING,
        ], $form->get('mode')->getConfig()->getOption('choices'));
    }

    public function testSubmittingPopulatesTheDto(): void
    {
        $form = $this->factory->create(BulkImportType::class);

        $form->submit(['data' => "domain\nexample.test", 'mode' => BulkImport::MODE_CREATE_ONLY]);

        $this->assertTrue($form->isSynchronized());
        $item = $form->getData();
        $this->assertInstanceOf(BulkImport::class, $item);
        $this->assertSame("domain\nexample.test", $item->getData());
        $this->assertSame(BulkImport::MODE_CREATE_ONLY, $item->getMode());
    }
}
