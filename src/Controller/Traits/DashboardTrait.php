<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use Playtini\EasyAdminHelperBundle\Dashboard\DashboardHelper;
use Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminMenuInterface;

trait DashboardTrait
{
    use DashboardCustomConstructorTrait;

    public function __construct(
        protected EasyAdminMenuInterface $easyAdminMenu,
        protected DashboardHelper $dashboardHelper,
    ) {}
}
