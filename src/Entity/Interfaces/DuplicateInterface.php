<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Entity\Interfaces;

interface DuplicateInterface
{
    public function duplicate(self $item): self;
}
