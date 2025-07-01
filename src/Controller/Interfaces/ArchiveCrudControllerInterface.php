<?php

namespace Playtini\EasyAdminHelperBundle\Controller\Interfaces;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

interface ArchiveCrudControllerInterface
{
    public function archive(AdminContext $context): RedirectResponse;

    public function createArchiveAction(): Action;

    public function isShownArchive(): bool;
}
