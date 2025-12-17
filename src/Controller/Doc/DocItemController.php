<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Doc;

use Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminContext;
use Playtini\EasyAdminHelperBundle\Frontmatter\FrontmatterParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/doc/{name}', name: 'admin_doc_item')]
class DocItemController extends AbstractController
{
    public function __construct(
        private readonly EasyAdminContext $easyAdminContext,
        private readonly FrontmatterParser $frontmatterParser,
        #[Autowire('%kernel.project_dir%/doc')]
        private readonly string $docDir,
    ) {
    }

    public function __invoke(string $name): Response
    {
        $this->easyAdminContext->setMenuIndex('/admin/doc');

        $data = [];
        $content = 'Not found';

        $filename = $this->docDir . '/' . $name . '.md';
        if (is_file($filename)) {
            $result = $this->frontmatterParser->parseFileToHtml($filename);
            $content = $result->html;
            $data = $result->matter;
        }
        $data['title'] ??= $this->frontmatterParser->titleFromFilename($name);

        /** @noinspection PhpTemplateMissingInspection */
        return $this->render('@EasyAdminHelper/admin/doc/item.html.twig', [
            'ea' => $this->easyAdminContext->getContextProvider(),
            'data' => $data,
            'content' => $content,
        ]);
    }
}
