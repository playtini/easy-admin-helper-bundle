<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Traits;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\Traits\DataEntityTrait;

class DataEntityTraitTest extends TestCase
{
    public function testDefaultData(): void
    {
        $entity = $this->createEntity();
        $this->assertSame([], $entity->getData());
    }

    public function testSetData(): void
    {
        $entity = $this->createEntity();
        $result = $entity->setData(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $entity->getData());
        $this->assertSame($entity, $result);
    }

    public function testSetDataNullResetsToEmpty(): void
    {
        $entity = $this->createEntity();
        $entity->setData(['a' => 1]);
        $entity->setData(null);

        $this->assertSame([], $entity->getData());
    }

    public function testGetDataStringReturnsYamlEscapedAndLineBroken(): void
    {
        $entity = $this->createEntity();
        $entity->setData(['key' => 'value']);

        $out = $entity->getDataString();
        $this->assertStringContainsString('key: value', $out);
        // nl2br injects <br /> after each newline from Yaml::dump
        $this->assertStringContainsString('<br', $out);
    }

    public function testGetDataStringEscapesHtml(): void
    {
        $entity = $this->createEntity();
        $entity->setData(['html' => '<b>x</b>']);

        $out = $entity->getDataString();
        $this->assertStringContainsString('&lt;b&gt;', $out);
        $this->assertStringNotContainsString('<b>x</b>', $out);
    }

    public function testGetDataStringTruncates(): void
    {
        $entity = $this->createEntity();
        $entity->setData(['note' => str_repeat('a', 500)]);

        $out = $entity->getDataString(50);
        $this->assertStringEndsWith('...', $out);
        // 50 source chars then '...' then htmlspecialchars+nl2br are applied on the truncated string;
        // for a-only content these passes are identity, so the visible length stays bounded.
        $this->assertLessThanOrEqual(53, mb_strlen($out));
    }

    private function createEntity(): object
    {
        return new class {
            use DataEntityTrait;
        };
    }
}
