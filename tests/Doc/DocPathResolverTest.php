<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Doc;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Doc\DocPathResolver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DocPathResolverTest extends TestCase
{
    private DocPathResolver $resolver;
    private string $root;
    private string $baseDir;

    protected function setUp(): void
    {
        $this->resolver = new DocPathResolver();

        $root = sys_get_temp_dir() . '/doc_path_resolver_' . uniqid();
        self::assertTrue(mkdir($root . '/base/ops', 0777, true));
        self::assertTrue(mkdir($root . '/base/db', 0777, true));
        self::assertTrue(mkdir($root . '/base/sub', 0777, true));
        self::assertTrue(mkdir($root . '/basedocs', 0777, true));
        self::assertTrue(mkdir($root . '/outside', 0777, true));
        file_put_contents($root . '/base/ops/known-issues.md', '# known issues');
        file_put_contents($root . '/base/db/schema.md', '# schema');
        file_put_contents($root . '/basedocs/leak.md', '# leak');
        file_put_contents($root . '/outside/secret.md', '# secret');
        self::assertTrue(symlink($root . '/outside', $root . '/base/escape'));
        self::assertTrue(symlink($root . '/outside/secret.md', $root . '/base/linked.md'));

        // realpath() because sys_get_temp_dir() is itself symlinked on macOS
        $this->root = (string)realpath($root);
        $this->baseDir = $this->root . '/base';
    }

    protected function tearDown(): void
    {
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($entries as $entry) {
            // symlinked dirs are not descended into (hasChildren() defaults to $allowLinks = false),
            // so the link itself is yielded as a leaf and must be unlinked, not rmdir'd
            if ($entry->isLink() || $entry->isFile()) {
                unlink($entry->getPathname());
            } else {
                rmdir($entry->getPathname());
            }
        }
        rmdir($this->root);
    }

    public function testResolvesNestedFile(): void
    {
        $this->assertSame(
            $this->baseDir . '/ops/known-issues.md',
            $this->resolver->resolveFile($this->baseDir, 'ops/known-issues.md'),
        );
    }

    public function testResolvesBenignParentSegmentThatStaysInside(): void
    {
        $this->assertSame(
            $this->baseDir . '/ops/known-issues.md',
            $this->resolver->resolveFile($this->baseDir, 'db/../ops/known-issues.md'),
        );
    }

    public function testRejectsTraversalOutOfBaseDir(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, '../outside/secret.md'));
    }

    public function testRejectsDeepTraversalToAnExistingFile(): void
    {
        // climbs above the fixture root and back down: proves containment rejects it,
        // rather than the path merely failing to exist
        $deep = '../../' . basename($this->root) . '/outside/secret.md';

        $this->assertNull($this->resolver->resolveFile($this->baseDir, $deep));
    }

    public function testRejectsSiblingDirectoryWithSharedPrefix(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, '../basedocs/leak.md'));
    }

    public function testRejectsSymlinkPointingOutsideBaseDir(): void
    {
        self::assertTrue(is_link($this->baseDir . '/escape'));

        $this->assertNull($this->resolver->resolveFile($this->baseDir, 'escape/secret.md'));
    }

    public function testRejectsSymlinkedFilePointingOutsideBaseDir(): void
    {
        self::assertTrue(is_link($this->baseDir . '/linked.md'));

        $this->assertNull($this->resolver->resolveFile($this->baseDir, 'linked.md'));
    }

    public function testRejectsDirectory(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, 'sub'));
    }

    public function testRejectsMissingFile(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, 'nope.md'));
    }

    public function testRejectsEmptyRelativePath(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, ''));
    }

    public function testRejectsBareNullByteInRelativePathWithoutError(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, "\0"));
    }

    public function testRejectsNullByteAppendedToValidRelativePathWithoutError(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, "ops/known-issues.md\0"));
    }

    public function testRejectsNullByteInBaseDirWithoutError(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir . "\0", 'ops/known-issues.md'));
    }

    public function testRejectsMissingBaseDir(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->root . '/nope', 'ops/known-issues.md'));
    }

    public function testRejectsAbsolutePathOutsideBaseDir(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, $this->root . '/outside/secret.md'));
    }

    public function testRejectsAbsoluteSystemPath(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, '/etc/hosts'));
    }
}
