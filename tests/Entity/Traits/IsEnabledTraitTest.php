<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Interfaces\ArchivableInterface;
use Playtini\EasyAdminHelperBundle\Entity\Traits\ArchivableEntityTrait;
use Playtini\EasyAdminHelperBundle\Entity\Traits\IsEnabledTrait;

class IsEnabledTraitTest extends TestCase
{
    public function testEnabledByDefault(): void
    {
        $entity = $this->createEntity();
        $this->assertTrue($entity->isEnabled());
    }

    public function testSetIsEnabled(): void
    {
        $entity = $this->createEntity();
        $result = $entity->setIsEnabled(false);

        $this->assertFalse($entity->isEnabled());
        $this->assertSame($entity, $result);
    }

    public function testSetIsEnabledNull(): void
    {
        $entity = $this->createEntity();
        $entity->setIsEnabled(null);

        $this->assertTrue($entity->isEnabled());
    }

    public function testIsEnabledFalseWhenArchived(): void
    {
        $entity = $this->createArchivableEntity();
        $entity->archive();

        $this->assertFalse($entity->isEnabled());
    }

    private function createEntity(): object
    {
        return new class {
            use IsEnabledTrait;
        };
    }

    private function createArchivableEntity(): object
    {
        return new class implements ArchivableInterface {
            use ArchivableEntityTrait;
            use IsEnabledTrait;
        };
    }
}
