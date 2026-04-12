<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\NameEntityTrait;

class NameEntityTraitTest extends TestCase
{
    public function testDefaultName(): void
    {
        $entity = $this->createEntity();
        $this->assertSame('', $entity->getName());
        $this->assertSame('', (string)$entity);
    }

    public function testSetName(): void
    {
        $entity = $this->createEntity();
        $result = $entity->setName('Test');

        $this->assertSame('Test', $entity->getName());
        $this->assertSame('Test', (string)$entity);
        $this->assertSame($entity, $result);
    }

    public function testSetNameNull(): void
    {
        $entity = $this->createEntity();
        $entity->setName(null);

        $this->assertSame('', $entity->getName());
    }

    public function testSetNameTruncatesAt255(): void
    {
        $entity = $this->createEntity();
        $entity->setName(str_repeat('a', 300));

        $this->assertSame(255, mb_strlen($entity->getName()));
    }

    private function createEntity(): object
    {
        return new class {
            use NameEntityTrait;
        };
    }
}
