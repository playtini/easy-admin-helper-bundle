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

    public function testInitializeCreatedAtPopulatesNullProperty(): void
    {
        $entity = $this->createEntity();
        $this->assertNull($this->rawCreatedAt($entity));

        $entity->initializeCreatedAt();

        $this->assertInstanceOf(DateTimeInterface::class, $this->rawCreatedAt($entity));
    }

    public function testInitializeCreatedAtKeepsExistingValue(): void
    {
        $entity = $this->createEntity();
        $entity->setCreatedAt(new \DateTimeImmutable('2024-06-01'));

        $entity->initializeCreatedAt();

        $this->assertEquals('2024-06-01', $entity->getCreatedAt()->format('Y-m-d'));
    }

    private function rawCreatedAt(object $entity): ?DateTimeInterface
    {
        return new \ReflectionProperty($entity, 'createdAt')->getValue($entity);
    }

    private function createEntity(): object
    {
        return new class {
            use CreatableEntityTrait;
        };
    }
}
