<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Frontmatter;

readonly class FrontmatterResult
{
    public function __construct(
        public string $html,
        public array $matter,
        public string $body,
    ) {
    }

    public function getTitle(?string $default = null): ?string
    {
        return $this->matter['title'] ?? $default;
    }
}
