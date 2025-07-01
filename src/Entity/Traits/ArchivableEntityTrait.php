<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gupalo\DateUtils\Dat;
use Playtini\EasyAdminHelperBundle\Entity\Interfaces\IsEnabledEntityInterface;

trait ArchivableEntityTrait
{
    use CreatableEntityTrait;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $archivedAt = null;

    public function isArchived(): bool
    {
        return $this->getArchivedAt() !== null;
    }

    public function archive(): self
    {
        if (!$this->isArchived()) {
            $this->setArchivedAt(Dat::create());
        }
        if ($this instanceof IsEnabledEntityInterface && $this->isEnabled()) {
            $this->setIsEnabled(false);
        }

        return $this;
    }

    public function unarchive(): self
    {
        if ($this->isArchived()) {
            $this->setArchivedAt(null);
        }

        return $this;
    }

    public function getArchivedAt(): ?DateTimeInterface
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?DateTimeInterface $archivedAt): self
    {
        $this->archivedAt = Dat::createNull($archivedAt);

        return $this;
    }
}
