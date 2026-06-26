<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Dashboard;

interface EasyAdminMenuInterface
{
    public function getTitle(): string;
    public function configureMenuItems(): iterable;
}
