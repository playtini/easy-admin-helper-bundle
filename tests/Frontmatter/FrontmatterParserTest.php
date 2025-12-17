<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Frontmatter;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Frontmatter\FrontmatterParser;
use Playtini\EasyAdminHelperBundle\Frontmatter\FrontmatterResult;

class FrontmatterParserTest extends TestCase
{
    private FrontmatterParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FrontmatterParser();
    }

    public function testParseStringWithFrontmatter(): void
    {
        $content = <<<MD
---
title: Test Title
author: John
---
# Hello World

This is content.
MD;

        $document = $this->parser->parseString($content);

        $this->assertEquals('Test Title', $document->matter('title'));
        $this->assertEquals('John', $document->matter('author'));
        $this->assertStringContainsString('# Hello World', $document->body());
        $this->assertStringContainsString('This is content.', $document->body());
    }

    public function testParseStringWithoutFrontmatter(): void
    {
        $content = <<<MD
# Just Markdown

No frontmatter here.
MD;

        $document = $this->parser->parseString($content);

        $this->assertNull($document->matter('title'));
        $this->assertEmpty($document->matter());
        $this->assertStringContainsString('# Just Markdown', $document->body());
    }

    public function testParseStringWithHorizontalRuleInBodyNoFrontmatter(): void
    {
        $content = <<<MD
# Document Title

Some text before separator.

---

Some text after separator.

---

Final section.
MD;

        $document = $this->parser->parseString($content);

        $this->assertEmpty($document->matter());
        $this->assertStringContainsString('# Document Title', $document->body());
        $this->assertStringContainsString('Some text before separator.', $document->body());
        $this->assertStringContainsString('---', $document->body());
        $this->assertStringContainsString('Some text after separator.', $document->body());
        $this->assertStringContainsString('Final section.', $document->body());
    }

    public function testParseStringWithHorizontalRuleInBodyWithFrontmatter(): void
    {
        $content = <<<MD
---
title: My Doc
---
# Document Title

Some text before separator.

---

Some text after separator.
MD;

        $document = $this->parser->parseString($content);

        $this->assertEquals('My Doc', $document->matter('title'));
        $this->assertStringContainsString('# Document Title', $document->body());
        $this->assertStringContainsString('---', $document->body());
        $this->assertStringContainsString('Some text after separator.', $document->body());
    }

    public function testParseStringToHtml(): void
    {
        $content = <<<MD
---
title: My Page
---
# Heading

Paragraph text.
MD;

        $result = $this->parser->parseStringToHtml($content);

        $this->assertInstanceOf(FrontmatterResult::class, $result);
        $this->assertEquals('My Page', $result->getTitle());
        $this->assertStringContainsString('<h1>Heading</h1>', $result->html);
        $this->assertStringContainsString('<p>Paragraph text.</p>', $result->html);
        $this->assertStringContainsString('# Heading', $result->body);
    }

    public function testParseStringToHtmlWithLinks(): void
    {
        $content = <<<MD
---
title: Links Test
---
Visit [example](https://example.com) for more.
MD;

        $result = $this->parser->parseStringToHtml($content);

        $this->assertStringContainsString('<a href="https://example.com">example</a>', $result->html);
    }

    public function testParseStringToHtmlWithCodeBlock(): void
    {
        $content = <<<'MD'
---
title: Code Test
---
```php
echo "Hello";
```
MD;

        $result = $this->parser->parseStringToHtml($content);

        $this->assertStringContainsString('<code', $result->html);
        $this->assertStringContainsString('echo', $result->html);
    }

    public function testTitleFromFilename(): void
    {
        $this->assertEquals('My Document', $this->parser->titleFromFilename('my_document.md'));
        $this->assertEquals('My Document', $this->parser->titleFromFilename('/path/to/my_document.md'));
        $this->assertEquals('Simple', $this->parser->titleFromFilename('simple.md'));
        $this->assertEquals('Already Capitalized', $this->parser->titleFromFilename('already_capitalized.md'));
        $this->assertEquals('Multi Word Title Here', $this->parser->titleFromFilename('multi_word_title_here.md'));
    }

    public function testTitleFromFilenameWithoutExtension(): void
    {
        $this->assertEquals('Document', $this->parser->titleFromFilename('document'));
    }

    public function testParseFileToHtml(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_frontmatter_' . uniqid() . '.md';
        $content = <<<MD
---
title: File Test
category: testing
---
# File Content

This is from a file.
MD;
        file_put_contents($tempFile, $content);

        try {
            $result = $this->parser->parseFileToHtml($tempFile);

            $this->assertEquals('File Test', $result->getTitle());
            $this->assertEquals('testing', $result->matter['category']);
            $this->assertStringContainsString('<h1>File Content</h1>', $result->html);
        } finally {
            unlink($tempFile);
        }
    }

    public function testParseFile(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_frontmatter_' . uniqid() . '.md';
        $content = <<<MD
---
key: value
---
Body content
MD;
        file_put_contents($tempFile, $content);

        try {
            $document = $this->parser->parseFile($tempFile);

            $this->assertEquals('value', $document->matter('key'));
            $this->assertStringContainsString('Body content', $document->body());
        } finally {
            unlink($tempFile);
        }
    }
}
