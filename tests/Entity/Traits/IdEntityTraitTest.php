<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\IdEntityTrait;

class IdEntityTraitTest extends TestCase
{
    public function testDefaultIdIsNull(): void
    {
        $entity = $this->createEntity();
        $this->assertNull($entity->getId());
    }

    public function testIdReturnsAssignedValue(): void
    {
        $entity = $this->createEntity();

        // Doctrine normally assigns id on persist; emulate via reflection to verify the accessor.
        $ref = new \ReflectionClass($entity);
        $prop = $ref->getProperty('id');
        $prop->setValue($entity, 42);

        $this->assertSame(42, $entity->getId());
    }

    private function createEntity(): object
    {
        return new class {
            use IdEntityTrait;
        };
    }
}
