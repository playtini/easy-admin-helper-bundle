<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gupalo\DateUtils\Dat;

trait CreatableEntityTrait
{
    use IdEntityTrait;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeInterface $createdAt = null;

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt ?? Dat::now();
    }

    public function setCreatedAt(?DateTimeInterface $createdAt = null): self
    {
        $this->createdAt = Dat::create($createdAt);

        return $this;
    }
}
