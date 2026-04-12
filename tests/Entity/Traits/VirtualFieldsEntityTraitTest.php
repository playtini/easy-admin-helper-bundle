<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\VirtualFieldsEntityTrait;

class VirtualFieldsEntityTraitTest extends TestCase
{
    public function testDefaults(): void
    {
        $entity = new class {
            use VirtualFieldsEntityTrait;
        };

        $this->assertSame('', $entity->virtual());
        $this->assertSame('', $entity->virtualString());
        $this->assertSame(0, $entity->virtualInt());
        $this->assertSame(0.0, $entity->virtualFloat());
        $this->assertFalse($entity->virtualBool());
        $this->assertNull($entity->virtualStringNull());
        $this->assertNull($entity->virtualIntNull());
        $this->assertNull($entity->virtualFloatNull());
        $this->assertNull($entity->virtualBoolNull());
    }
}
