<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\CommentEntityTrait;
use Playtini\EasyAdminHelperBundle\Entity\Traits\ShortCommentEntityTrait;

class CommentEntityTraitTest extends TestCase
{
    public function testDefaultComment(): void
    {
        $entity = $this->createEntity();
        $this->assertSame('', $entity->getComment());
    }

    public function testSetComment(): void
    {
        $entity = $this->createEntity();
        $result = $entity->setComment('Hello');

        $this->assertSame('Hello', $entity->getComment());
        $this->assertSame($entity, $result);
    }

    public function testSetCommentNull(): void
    {
        $entity = $this->createEntity();
        $entity->setComment(null);

        $this->assertSame('', $entity->getComment());
    }

    public function testShortCommentTruncatesAt1024(): void
    {
        $entity = $this->createShortEntity();
        $entity->setComment(str_repeat('a', 2000));

        $this->assertSame(1024, mb_strlen($entity->getComment()));
    }

    private function createEntity(): object
    {
        return new class {
            use CommentEntityTrait;
        };
    }

    private function createShortEntity(): object
    {
        return new class {
            use ShortCommentEntityTrait;
        };
    }
}
