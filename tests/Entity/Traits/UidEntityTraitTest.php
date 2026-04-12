<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\UidEntityTrait;

class UidEntityTraitTest extends TestCase
{
    public function testDefaultUid(): void
    {
        $entity = $this->createEntity();
        $this->assertSame('', $entity->getUid());
    }

    public function testSetUid(): void
    {
        $entity = $this->createEntity();
        $result = $entity->setUid('abc123');

        $this->assertSame('abc123', $entity->getUid());
        $this->assertSame($entity, $result);
    }

    public function testSetUidNullGeneratesUid(): void
    {
        $entity = $this->createEntity();
        $entity->setUid(null);

        $this->assertNotEmpty($entity->getUid());
    }

    public function testSetUidTruncatesAt32(): void
    {
        $entity = $this->createEntity();
        $entity->setUid(str_repeat('x', 50));

        $this->assertSame(32, mb_strlen($entity->getUid()));
    }

    private function createEntity(): object
    {
        return new class {
            use UidEntityTrait;
        };
    }
}
