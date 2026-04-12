<?php

namespace Playtini\EasyAdminHelperBundle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClassLoadableTest extends TestCase
{
    #[DataProvider('classProvider')]
    public function testClassIsLoadable(string $class): void
    {
        $reflection = new ReflectionClass($class);

        $this->assertSame($class, $reflection->getName());
    }

    public static function classProvider(): iterable
    {
        $srcDir = dirname(__DIR__) . '/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($srcDir . '/', '', $file->getPathname());
            $class = 'Playtini\\EasyAdminHelperBundle\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

            if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
                continue;
            }

            yield $class => [$class];
        }
    }
}
