<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Interfaces;

use DateTimeInterface;

interface ArchivableInterface
{
    public function isArchived(): bool;
    public function archive(): self;
    public function unarchive(): self;
    public function getArchivedAt(): ?DateTimeInterface;
    public function setArchivedAt(?DateTimeInterface $archivedAt): self;
}
