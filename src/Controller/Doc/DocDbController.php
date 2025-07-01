<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Doc;

use Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/doc/db', name: 'admin_doc_db')]
class DocDbController extends AbstractController
{
    public function __construct(
        private readonly EasyAdminContext $easyAdminContext,
        #[Autowire('%kernel.project_dir%/var/doc/db')]
        private readonly string $dbDocDir,
    ) {
    }

    public function __invoke(): Response
    {
        $this->easyAdminContext->setMenuIndex('/admin/doc');

        if (!is_dir($this->dbDocDir)) {
            /** @noinspection MkdirRaceConditionInspection */
            mkdir($this->dbDocDir, 0777, true);
        }

        /** @noinspection PhpTemplateMissingInspection */
        return $this->render('@EasyAdminHelper/admin/doc/db.html.twig', [
            'ea' => $this->easyAdminContext->getContextProvider(),
            'diagrams' => Finder::create()->in($this->dbDocDir)->files()->name('*.svg')->sortByName(),
        ]);
    }
}
