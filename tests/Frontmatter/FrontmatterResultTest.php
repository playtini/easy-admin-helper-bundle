<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Frontmatter;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Frontmatter\FrontmatterResult;

class FrontmatterResultTest extends TestCase
{
    public function testConstructorAndProperties(): void
    {
        $result = new FrontmatterResult(
            html: '<p>Hello</p>',
            matter: ['title' => 'Test', 'author' => 'John'],
            body: 'Hello',
        );

        $this->assertEquals('<p>Hello</p>', $result->html);
        $this->assertEquals(['title' => 'Test', 'author' => 'John'], $result->matter);
        $this->assertEquals('Hello', $result->body);
    }

    public function testGetTitleWithExistingTitle(): void
    {
        $result = new FrontmatterResult(
            html: '',
            matter: ['title' => 'My Title'],
            body: '',
        );

        $this->assertEquals('My Title', $result->getTitle());
        $this->assertEquals('My Title', $result->getTitle('Default'));
    }

    public function testGetTitleWithoutTitle(): void
    {
        $result = new FrontmatterResult(
            html: '',
            matter: ['author' => 'John'],
            body: '',
        );

        $this->assertNull($result->getTitle());
        $this->assertEquals('Default Title', $result->getTitle('Default Title'));
    }

    public function testGetTitleWithEmptyMatter(): void
    {
        $result = new FrontmatterResult(
            html: '<p>Content</p>',
            matter: [],
            body: 'Content',
        );

        $this->assertNull($result->getTitle());
        $this->assertEquals('Fallback', $result->getTitle('Fallback'));
    }

    public function testReadonlyProperties(): void
    {
        $result = new FrontmatterResult(
            html: '<p>Test</p>',
            matter: ['key' => 'value'],
            body: 'Test',
        );

        $reflection = new \ReflectionClass($result);
        $this->assertTrue($reflection->isReadOnly());
    }
}
