<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class DashboardHelper
{
    public function __construct(
        public AdminUrlGenerator $adminUrlGenerator,
        public AdminContextProvider $adminContextProvider,
        public RequestStack $requestStack,
    ) {
    }

    public function getRequest(): Request
    {
        return $this->requestStack->getMainRequest() ?? Request::createFromGlobals();
    }
}
