<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Entity\Interfaces;

interface NameEntityInterface extends IdEntityInterface
{
    public function getName(): string;

    public function setName(?string $name): static;

    public function __toString(): string;
}
