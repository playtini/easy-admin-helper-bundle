<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Frontmatter;

use Parsedown;
use Spatie\YamlFrontMatter\Document;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class FrontmatterParser
{
    public function parseFile(string $filename): Document
    {
        return $this->parseString((string) file_get_contents($filename));
    }

    public function parseString(string $content): Document
    {
        if (!$this->hasFrontmatter($content)) {
            $content = "---\n---\n" . $content;
        }

        return YamlFrontMatter::markdownCompatibleParse($content);
    }

    private function hasFrontmatter(string $content): bool
    {
        return str_starts_with(ltrim($content), '---');
    }

    public function parseFileToHtml(string $filename): FrontmatterResult
    {
        $document = $this->parseFile($filename);

        return $this->documentToResult($document);
    }

    public function parseStringToHtml(string $content): FrontmatterResult
    {
        $document = $this->parseString($content);

        return $this->documentToResult($document);
    }

    private function documentToResult(Document $document): FrontmatterResult
    {
        return new FrontmatterResult(
            html: new Parsedown()->text($document->body()),
            matter: $document->matter(),
            body: $document->body(),
        );
    }

    public function titleFromFilename(string $filename): string
    {
        return ucwords(str_replace('_', ' ', basename($filename, '.md')));
    }
}
