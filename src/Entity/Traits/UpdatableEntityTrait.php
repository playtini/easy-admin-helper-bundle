<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gupalo\DateUtils\Dat;

trait UpdatableEntityTrait
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = Dat::now();
    }
}
