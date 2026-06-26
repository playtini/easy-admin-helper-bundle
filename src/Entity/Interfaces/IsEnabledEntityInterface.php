<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Entity\Interfaces;

interface IsEnabledEntityInterface
{
    public function isEnabled(): bool;

    public function setIsEnabled(?bool $isEnabled): static;
}
