<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Doc;

use Playtini\EasyAdminHelperBundle\Doc\DocPathResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/doc/diagram/{filename}', name: 'admin_doc_diagram')]
class DocDiagramController extends AbstractController
{

    public function __construct(
        private readonly DocPathResolver $docPathResolver,
        #[Autowire('%kernel.project_dir%/var/doc/db')]
        private readonly string $dbDocDir,
    ) {}

    public function __invoke(string $filename): Response
    {
        $path = $this->docPathResolver->resolveFile($this->dbDocDir, $filename);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        /** @noinspection UseControllerShortcuts */
        return new BinaryFileResponse($path, public: false);
    }
}
