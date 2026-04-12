<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\CreatableEntityTrait;

class CreatableEntityTraitTest extends TestCase
{
    public function testGetCreatedAtDefault(): void
    {
        $entity = $this->createEntity();
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreatedAt());
    }

    public function testSetCreatedAt(): void
    {
        $entity = $this->createEntity();
        $date = new \DateTimeImmutable('2024-06-01');
        $result = $entity->setCreatedAt($date);

        $this->assertSame($entity, $result);
        $this->assertEquals('2024-06-01', $entity->getCreatedAt()->format('Y-m-d'));
    }

    public function testSetCreatedAtNull(): void
    {
        $entity = $this->createEntity();
        $entity->setCreatedAt(null);

        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreatedAt());
    }

    private function createEntity(): object
    {
        return new class {
            use CreatableEntityTrait;
        };
    }
}
