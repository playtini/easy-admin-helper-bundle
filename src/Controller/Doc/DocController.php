<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Doc;

use Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminContext;
use Playtini\EasyAdminHelperBundle\Frontmatter\FrontmatterParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/doc', name: 'admin_doc')]
class DocController extends AbstractController
{
    public function __construct(
        private readonly EasyAdminContext $easyAdminContext,
        private readonly FrontmatterParser $frontmatterParser,
        #[Autowire('%kernel.project_dir%/doc')]
        private readonly string $docDir,
    ) {
    }

    public function __invoke(): Response
    {
        $items = [];
        $files = Finder::create()->in($this->docDir)->files()->name('*.md')->sortByName();
        foreach ($files as $file) {
            if (!is_file($file->getPathname())) {
                continue;
            }

            $name = $file->getBasename('.'.$file->getExtension());
            $document = $this->frontmatterParser->parseFile($file->getPathname());
            $item = [
                'url' => $this->generateUrl('admin_doc_item', ['name' => $name]),
                'title' => $document->matter('title') ?? $this->frontmatterParser->titleFromFilename($name),
                'data' => $document->matter(),
            ];

            $items[] = $item;
        }

        /** @noinspection PhpTemplateMissingInspection */
        return $this->render('@EasyAdminHelper/admin/doc/index.html.twig', [
            'ea' => $this->easyAdminContext->getContextProvider(),
            'items' => $items,
        ]);
    }
}
