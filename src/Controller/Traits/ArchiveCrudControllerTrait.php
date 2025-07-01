<?php

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Gupalo\BrowserNotifier\BrowserNotifier;
use Playtini\EasyAdminHelperBundle\Entity\Interfaces\ArchivableInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @property BrowserNotifier $browserNotifier
 * @property AdminUrlGenerator $adminUrlGenerator
 * @method RedirectResponse redirect(string $url, int $status = 302)
 */
trait ArchiveCrudControllerTrait
{
    use SaveCrudControllerTrait;

    protected bool $isShownArchive = false;

    public function archive(AdminContext $context): RedirectResponse
    {
        $item = $context->getEntity()->getInstance();

        if ($item instanceof ArchivableInterface) {
            $className = preg_replace('#^.*\\\\#', '', self::getEntityFqcn());
            if (!$item->isArchived()) {
                $this->browserNotifier->success(sprintf('Archived %s "%s"', $className, $item));
                $item->archive();
                $this->save($item, true);
            } else {
                $this->browserNotifier->warning(sprintf('%s "%s" was already archived', $className, $item));
            }
        } else {
            $this->browserNotifier->warning('Item is not archivable');
        }

        return $this->redirect(
            $this->adminUrlGenerator->get('referrer') ?:
                $this->adminUrlGenerator->setController(__CLASS__)->setAction(Action::INDEX)->removeReferrer()->generateUrl()
        );
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        /** @noinspection PhpUndefinedClassInspection */
        $result = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $this->applyQueryBuilderArchived($result, $searchDto);

        return $result;
    }

    protected function applyQueryBuilderArchived(QueryBuilder $queryBuilder, SearchDto $searchDto): QueryBuilder
    {
        $this->isShownArchive = !empty($searchDto->getAppliedFilters()['archivedAt']);
        if (!$this->isShownArchive) {
            $queryBuilder->andWhere('entity.archivedAt IS NULL');
        }

        return $queryBuilder;
    }

    public function configureActions(Actions $actions): Actions
    {
        $archiveAction = $this->createArchiveAction();

        /** @noinspection PhpUndefinedClassInspection */
        return parent::configureActions($actions)
            ->disable(Action::DELETE)
            ->add(Crud::PAGE_EDIT, $archiveAction);
    }

    public function createArchiveAction(): Action
    {
        return Action::new('Archive')
            ->linkToCrudAction('archive')
            ->addCssClass('text-danger bg-dark')
            ->displayIf(static fn($e) => !$e->isArchived());
    }

    public function isShownArchive(): bool
    {
        return $this->isShownArchive;
    }
}
