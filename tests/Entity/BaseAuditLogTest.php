<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog;
use Playtini\EasyAdminHelperBundle\Tests\Entity\Fixture\ConcreteAuditLog;
use ReflectionClass;
use ReflectionProperty;

class BaseAuditLogTest extends TestCase
{
    public function testCreatePopulatesEveryField(): void
    {
        $item = ConcreteAuditLog::create(
            'alice',
            'admin_dashboard',
            ['page' => 2],
            'https://example.test/admin?page=2',
            'GET',
            200,
            '10.0.0.1',
            42,
        );

        $this->assertSame('alice', $item->getUsername());
        $this->assertSame('admin_dashboard', $item->getRouteName());
        $this->assertSame(['page' => 2], $item->getRouteParams());
        $this->assertSame('https://example.test/admin?page=2', $item->getUrl());
        $this->assertSame('GET', $item->getHttpMethod());
        $this->assertSame(200, $item->getStatusCode());
        $this->assertSame('10.0.0.1', $item->getIp());
        $this->assertSame(42, $item->getResponseTimeMs());
    }

    public function testCreateReturnsTheConcreteSubclassNotTheBase(): void
    {
        $item = ConcreteAuditLog::create('a', 'r', null, 'u', 'GET', 200, '1.2.3.4', 1);

        $this->assertInstanceOf(ConcreteAuditLog::class, $item);
    }

    public function testCreateAcceptsNullRouteParams(): void
    {
        $item = ConcreteAuditLog::create('a', 'r', null, 'u', 'GET', 200, '1.2.3.4', 1);

        $this->assertNull($item->getRouteParams());
    }

    public function testDefaultsAreEmptyBeforeCreate(): void
    {
        $item = new ConcreteAuditLog();

        $this->assertSame('', $item->getUsername());
        $this->assertSame('', $item->getRouteName());
        $this->assertNull($item->getRouteParams());
        $this->assertSame('', $item->getUrl());
        $this->assertSame('', $item->getHttpMethod());
        $this->assertSame(0, $item->getStatusCode());
        $this->assertSame('', $item->getIp());
        $this->assertSame(0, $item->getResponseTimeMs());
    }

    public function testIdComesFromCreatableEntityTraitAndIsNullBeforePersist(): void
    {
        $item = new ConcreteAuditLog();

        $this->assertNull($item->getId());
    }

    public function testCreatedAtLifecycleCallbackIsInherited(): void
    {
        $item = new ConcreteAuditLog();
        $item->initializeCreatedAt();

        $this->assertInstanceOf(DateTimeInterface::class, $item->getCreatedAt());
    }

    public function testIsMappedSuperclass(): void
    {
        $attributes = new ReflectionClass(BaseAuditLog::class)->getAttributes(ORM\MappedSuperclass::class);

        $this->assertCount(1, $attributes);
    }

    public function testIsAbstract(): void
    {
        $this->assertTrue(new ReflectionClass(BaseAuditLog::class)->isAbstract());
    }

    /**
     * Guards the claim the adoption gate rests on: these column definitions are
     * copied from the forks' inlined AuditLog entities, so the generated DDL is
     * unchanged. A change here is a schema change in every adopting project.
     *
     * @param array<string, mixed> $expected
     */
    #[DataProvider('columnProvider')]
    public function testColumnDefinitionMatchesTheForkSchema(string $property, array $expected): void
    {
        $attributes = new ReflectionProperty(BaseAuditLog::class, $property)
            ->getAttributes(ORM\Column::class);
        $this->assertCount(1, $attributes, sprintf('%s has no #[ORM\Column]', $property));

        $column = $attributes[0]->newInstance();

        $this->assertSame($expected['type'] ?? null, $column->type, sprintf('%s type', $property));
        $this->assertSame($expected['length'] ?? null, $column->length, sprintf('%s length', $property));
        $this->assertSame($expected['nullable'] ?? false, $column->nullable, sprintf('%s nullable', $property));
    }

    /** @return iterable<string, array{string, array<string, mixed>}> */
    public static function columnProvider(): iterable
    {
        yield 'username' => ['username', ['length' => 255]];
        yield 'routeName' => ['routeName', ['length' => 255]];
        yield 'routeParams' => ['routeParams', ['type' => Types::JSON, 'nullable' => true]];
        yield 'url' => ['url', ['length' => 2048]];
        yield 'httpMethod' => ['httpMethod', ['length' => 10]];
        yield 'statusCode' => ['statusCode', ['type' => Types::SMALLINT]];
        yield 'ip' => ['ip', ['length' => 45]];
        yield 'responseTimeMs' => ['responseTimeMs', []];
    }
}
