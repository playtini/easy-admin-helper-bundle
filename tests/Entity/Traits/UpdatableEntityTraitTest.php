<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\UpdatableEntityTrait;

class UpdatableEntityTraitTest extends TestCase
{
    public function testDefaultUpdatedAtIsNull(): void
    {
        $entity = $this->createEntity();
        $this->assertNull($entity->getUpdatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $entity = $this->createEntity();
        $date = new DateTimeImmutable('2026-01-02 03:04:05');

        $result = $entity->setUpdatedAt($date);

        $this->assertSame($date, $entity->getUpdatedAt());
        $this->assertSame($entity, $result);
    }

    public function testSetUpdatedAtNull(): void
    {
        $entity = $this->createEntity();
        $entity->setUpdatedAt(new DateTimeImmutable());
        $entity->setUpdatedAt(null);

        $this->assertNull($entity->getUpdatedAt());
    }

    public function testRefreshUpdatedAtSetsCurrentTime(): void
    {
        $entity = $this->createEntity();
        $before = new DateTimeImmutable();
        $entity->refreshUpdatedAt();
        $after = new DateTimeImmutable();

        $stamp = $entity->getUpdatedAt();
        $this->assertNotNull($stamp);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $stamp->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $stamp->getTimestamp());
    }

    private function createEntity(): object
    {
        return new class {
            use UpdatableEntityTrait;
        };
    }
}
