<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Playtini\EasyAdminHelperBundle\Dashboard\DashboardHelper;
use Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminMenuInterface;

trait DashboardTrait
{
    public function __construct(
        protected EasyAdminMenuInterface $easyAdminMenu,
        protected DashboardHelper $dashboardHelper,
    ) {}

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->easyAdminMenu->getTitle())
            ->renderContentMaximized()
        ;
    }

    public function configureCrud(): Crud
    {
        return $this->_configureCrud();
    }

    protected function _configureCrud(): Crud
    {
        return parent::configureCrud()
            ->setDateFormat('yyyy-MM-dd')
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->renderContentMaximized()
            ->setPaginatorPageSize(100)
            ->showEntityActionsInlined()
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureMenuItems(): iterable
    {
        return $this->easyAdminMenu->configureMenuItems();
    }

    public function configureActions(): Actions
    {
        return Actions::new();
    }

    public function setMenuIndex(?string $key = null): void
    {
        $key ??= $this->dashboardHelper->requestStack->getCurrentRequest()?->getPathInfo();

        $menuItems = $this->dashboardHelper->adminContextProvider->getContext()?->getMainMenu()->getItems();
        $index = $this->easyAdminMenu->menuCounters[$key] ?? null;

        if (null !== $index) {
            $menuItems[$index]->setSelected(true);
        }
    }
}
