<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Interfaces;

interface DuplicateInterface
{
    public function duplicate(self $item): self;
}
