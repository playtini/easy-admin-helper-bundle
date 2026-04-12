<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Interfaces\ArchivableInterface;
use Playtini\EasyAdminHelperBundle\Entity\Interfaces\IsEnabledEntityInterface;
use Playtini\EasyAdminHelperBundle\Entity\Traits\ArchivableEntityTrait;
use Playtini\EasyAdminHelperBundle\Entity\Traits\IsEnabledTrait;

class ArchivableEntityTraitTest extends TestCase
{
    public function testNotArchivedByDefault(): void
    {
        $entity = $this->createEntity();
        $this->assertFalse($entity->isArchived());
        $this->assertNull($entity->getArchivedAt());
    }

    public function testArchive(): void
    {
        $entity = $this->createEntity();
        $entity->archive();

        $this->assertTrue($entity->isArchived());
        $this->assertNotNull($entity->getArchivedAt());
    }

    public function testArchiveTwiceKeepsSameDate(): void
    {
        $entity = $this->createEntity();
        $entity->archive();
        $firstDate = $entity->getArchivedAt();

        $entity->archive();
        $this->assertEquals($firstDate, $entity->getArchivedAt());
    }

    public function testUnarchive(): void
    {
        $entity = $this->createEntity();
        $entity->archive();
        $entity->unarchive();

        $this->assertFalse($entity->isArchived());
        $this->assertNull($entity->getArchivedAt());
    }

    public function testUnarchiveWhenNotArchived(): void
    {
        $entity = $this->createEntity();
        $entity->unarchive();

        $this->assertFalse($entity->isArchived());
    }

    public function testSetArchivedAt(): void
    {
        $entity = $this->createEntity();
        $date = new \DateTimeImmutable('2024-01-15');
        $entity->setArchivedAt($date);

        $this->assertTrue($entity->isArchived());
        $this->assertNotNull($entity->getArchivedAt());
    }

    public function testArchiveDisablesIsEnabledEntity(): void
    {
        $entity = $this->createEnabledEntity();
        $this->assertTrue($entity->isEnabled());

        $entity->archive();

        $this->assertFalse($entity->isEnabled());
    }

    private function createEntity(): object
    {
        return new class implements ArchivableInterface {
            use ArchivableEntityTrait;
        };
    }

    private function createEnabledEntity(): object
    {
        return new class implements ArchivableInterface, IsEnabledEntityInterface {
            use ArchivableEntityTrait;
            use IsEnabledTrait;
        };
    }
}
