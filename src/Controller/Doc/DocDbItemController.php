<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Doc;

use Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminContext;
use Spatie\YamlFrontMatter\Document;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/doc/{name}', name: 'admin_doc_item')]
class DocDbItemController extends AbstractController
{
    public function __construct(
        private readonly EasyAdminContext $easyAdminContext,
        #[Autowire('%kernel.project_dir%/doc')]
        private readonly string $docDir,
    ) {
    }

    public function __invoke(string $name): Response
    {
        $this->easyAdminContext->setMenuIndex('/admin/doc');

        $data = [];
        $content = 'Not found';

        $filename = $this->docDir.'/'.$name.'.md';
        if (is_file($filename)) {
            $object = $this->parseFile($filename);
            /** @noinspection PhpParenthesesCanBeOmittedForNewCallInspection */
            $content = (new \Parsedown())->text($object->body());
            $data = $object->matter();
        }
        $data['title'] ??= ucwords(str_replace('_', ' ', $name));

        /** @noinspection PhpTemplateMissingInspection */
        return $this->render('@EasyAdminHelper/admin/doc/item.html.twig', [
            'ea' => $this->easyAdminContext->getContextProvider(),
            'data' => $data,
            'content' => $content,
        ]);
    }

    public function parseFile(string $filename): Document
    {
        return YamlFrontMatter::markdownCompatibleParse((string) file_get_contents($filename));
    }
}
