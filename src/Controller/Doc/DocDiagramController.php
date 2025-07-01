<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Doc;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/doc/diagram/{filename}', name: 'admin_doc_diagram')]
class DocDiagramController extends AbstractController
{

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/doc/db')]
        private readonly string $dbDocDir,
    ) {}

    public function __invoke(string $filename): Response
    {
        $filename = preg_replace('#(\.\.|/)#', '', $filename); // sanitize

        /** @noinspection UseControllerShortcuts */
        return new BinaryFileResponse($this->dbDocDir.'/'.$filename, public: false);
    }
}
