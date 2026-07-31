<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Doc;

/**
 * Resolves a user-supplied relative path against a base directory,
 * returning it only when it stays inside that directory.
 */
final class DocPathResolver
{
    public function resolveFile(string $baseDir, string $relativePath): ?string
    {
        // filesystem functions throw ValueError on null bytes since PHP 8
        if (str_contains($baseDir, "\0") || str_contains($relativePath, "\0")) {
            return null;
        }

        $base = realpath($baseDir);
        if ($base === false) {
            return null;
        }

        // realpath() collapses ".." and resolves symlinks, so containment
        // is checked on the canonical path, never on the raw input
        $path = realpath($base . '/' . $relativePath);
        if ($path === false) {
            return null;
        }

        // trailing separator: a sibling directory named "<base>docs" must not
        // satisfy the prefix match, and neither must the base directory itself
        if (!str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (!is_file($path)) {
            return null;
        }

        return $path;
    }
}
