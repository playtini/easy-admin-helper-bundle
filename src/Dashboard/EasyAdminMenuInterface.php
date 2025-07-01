<?php

namespace Playtini\EasyAdminHelperBundle\Dashboard;

interface EasyAdminMenuInterface
{
    public function getTitle(): string;
    public function configureMenuItems(): iterable;
}
