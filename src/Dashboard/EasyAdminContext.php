<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class EasyAdminContext
{
    public function __construct(
        public AdminUrlGenerator $adminUrlGenerator,
        public AdminContextProvider $adminContextProvider,
        public AdminContextFactory $contextFactory,
        public RequestStack $requestStack,
        public DashboardControllerInterface $dashboardController,
    ) {
    }

    public function getContextProvider(?string $menuKey = null): AdminContextProvider
    {
        $request = $this->requestStack->getMainRequest() ?? Request::createFromGlobals();

        $request->attributes->set(
            key: EA::CONTEXT_REQUEST_ATTRIBUTE,
            value: $this->contextFactory->create(
                request: $request,
                dashboardController: $this->dashboardController,
                crudController: null,
            ),
        );

        $this->setMenuIndex($menuKey);

        return $this->adminContextProvider;
    }

    public function setMenuIndex(?string $key = null): void
    {
        if (method_exists($this->dashboardController, 'setMenuIndex')) {
            $this->dashboardController->setMenuIndex($key);
        }
    }
}
