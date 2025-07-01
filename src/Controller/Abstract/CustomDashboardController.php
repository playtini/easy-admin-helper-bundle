<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Abstract;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Playtini\EasyAdminHelperBundle\Controller\Traits\DashboardTrait;

abstract class CustomDashboardController extends AbstractDashboardController
{
    use DashboardTrait;
}
