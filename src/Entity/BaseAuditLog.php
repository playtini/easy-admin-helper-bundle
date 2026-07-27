<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Playtini\EasyAdminHelperBundle\Entity\Traits\CreatableEntityTrait;

/**
 * Shared shape for per-request audit-log entities.
 *
 * A project subclasses this with its own #[ORM\Entity], repository binding and
 * indexes, so the physical table stays under the project's control while the
 * eight columns below stay identical everywhere:
 *
 *     #[ORM\Entity(repositoryClass: AuditLogRepository::class)]
 *     #[ORM\HasLifecycleCallbacks]
 *     #[ORM\Index(columns: ['created_at'])]
 *     class AuditLog extends BaseAuditLog {}
 *
 * #[ORM\HasLifecycleCallbacks] must be declared on the concrete class or
 * CreatableEntityTrait::initializeCreatedAt() never fires.
 *
 * Requires `doctrine.orm.auto_mapping: true` in the consuming application, or an
 * explicit mapping for the `Playtini\EasyAdminHelperBundle\Entity` namespace.
 * Without one, Doctrine treats this class as transient and the subclass maps to
 * zero columns — schema:update will then propose dropping every column from the
 * existing table rather than failing.
 *
 * CreatableEntityTrait already composes IdEntityTrait, so $id and getId() come
 * from it. Do not also `use IdEntityTrait` in a subclass — the two collide.
 */
#[ORM\MappedSuperclass]
abstract class BaseAuditLog
{
    use CreatableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $username = '';

    #[ORM\Column(length: 255)]
    private string $routeName = '';

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $routeParams = null;

    #[ORM\Column(length: 2048)]
    private string $url = '';

    #[ORM\Column(length: 10)]
    private string $httpMethod = '';

    #[ORM\Column(type: Types::SMALLINT)]
    private int $statusCode = 0;

    #[ORM\Column(length: 45)]
    private string $ip = '';

    #[ORM\Column]
    private int $responseTimeMs = 0;

    /**
     * Final so that `new static()` below is safe for every subclass. Doctrine
     * hydrates through newInstanceWithoutConstructor() and never calls it.
     * A consequence of being final: no subclass may declare its own constructor.
     */
    final public function __construct()
    {
    }

    /** @param array<string, mixed>|null $routeParams */
    public static function create(
        string $username,
        string $routeName,
        ?array $routeParams,
        string $url,
        string $httpMethod,
        int $statusCode,
        string $ip,
        int $responseTimeMs,
    ): static {
        $item = new static();
        $item->username = $username;
        $item->routeName = $routeName;
        $item->routeParams = $routeParams;
        $item->url = $url;
        $item->httpMethod = $httpMethod;
        $item->statusCode = $statusCode;
        $item->ip = $ip;
        $item->responseTimeMs = $responseTimeMs;

        return $item;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /** @return array<string, mixed>|null */
    public function getRouteParams(): ?array
    {
        return $this->routeParams;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getResponseTimeMs(): int
    {
        return $this->responseTimeMs;
    }
}
