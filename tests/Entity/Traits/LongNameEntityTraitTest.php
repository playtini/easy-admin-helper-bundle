<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\LongNameEntityTrait;

class LongNameEntityTraitTest extends TestCase
{
    public function testDefaultName(): void
    {
        $entity = $this->createEntity();
        $this->assertSame('', $entity->getName());
        $this->assertSame('', (string) $entity);
    }

    public function testSetName(): void
    {
        $entity = $this->createEntity();
        $result = $entity->setName('Some long-ish name');

        $this->assertSame('Some long-ish name', $entity->getName());
        $this->assertSame('Some long-ish name', (string) $entity);
        $this->assertSame($entity, $result);
    }

    public function testSetNameNullBecomesEmpty(): void
    {
        $entity = $this->createEntity();
        $entity->setName(null);

        $this->assertSame('', $entity->getName());
    }

    public function testSetNameTruncatesAt1000(): void
    {
        $entity = $this->createEntity();
        $entity->setName(str_repeat('a', 1500));

        $this->assertSame(1000, mb_strlen($entity->getName()));
    }

    public function testTraitProvidesIdAccessor(): void
    {
        $entity = $this->createEntity();
        $this->assertNull($entity->getId());
    }

    private function createEntity(): object
    {
        return new class {
            use LongNameEntityTrait;
        };
    }
}
