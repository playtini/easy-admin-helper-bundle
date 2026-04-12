<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\NameUniqueEntityTrait;

class NameUniqueEntityTraitTest extends TestCase
{
    public function testSetAndGetName(): void
    {
        $entity = new class {
            use NameUniqueEntityTrait;
        };

        $this->assertSame('', $entity->getName());
        $entity->setName('Unique');
        $this->assertSame('Unique', $entity->getName());
        $this->assertSame('Unique', (string)$entity);
    }

    public function testTruncatesAt255(): void
    {
        $entity = new class {
            use NameUniqueEntityTrait;
        };

        $entity->setName(str_repeat('b', 300));
        $this->assertSame(255, mb_strlen($entity->getName()));
    }
}
