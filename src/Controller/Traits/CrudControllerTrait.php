<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait CrudControllerTrait
{
    public function configureCrud(Crud $crud): Crud
    {
        return $this->_configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->_configureActions($actions);
    }

    protected function applyQueryBuilderJoin(QueryBuilder $queryBuilder, string $entity): QueryBuilder
    {
        $joined = false;
        /** @var Join $join */
        foreach ($queryBuilder->getDQLPart('join')['entity'] ?? [] as $join) {
            if ($join->getJoin() === 'entity.' . $entity) {
                $joined = true;
            }
        }
        if (!$joined) {
            $queryBuilder
                ->leftJoin('entity.' . $entity, $entity);
        }

        return $queryBuilder;
    }

    protected function parentCreateIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    protected function getAndUnsetFilter(SearchDto $searchDto, string $name): array
    {
        $value = $searchDto->getAppliedFilters()[$name] ?? null;
        if (!$value) {
            return [null, $searchDto];
        }

        $f = $searchDto->getAppliedFilters();
        unset($f[$name]);

        return [$value, new SearchDto(
            request: $searchDto->getRequest(),
            searchableProperties: $searchDto->getSearchableProperties(),
            query: $searchDto->getQuery(),
            defaultSort: [],
            customSort: $searchDto->getSort(),
            appliedFilters: $f,
        )];
    }

    protected function _configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDateFormat('yyyy-MM-dd')
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->renderContentMaximized()
            ->setPaginatorPageSize(100)
            ->showEntityActionsInlined()
            ->setDefaultSort(['id' => 'DESC'])
        ;
    }

    protected function _configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::NEW);
    }

    protected function _configureReadOnlyActions(Actions $actions): Actions
    {
        return $this->_configureActions($actions)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->disable(Action::NEW, Action::SAVE_AND_CONTINUE, Action::SAVE_AND_ADD_ANOTHER, Action::SAVE_AND_RETURN, Action::DELETE);
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function detail(AdminContext $context)
    {
        if (!property_exists($this, 'adminUrlGenerator')) {
            return parent::detail($context);
        }

        $entity = $context->getEntity();

        if (!$entity->isAccessible()) {
            throw new NotFoundHttpException(sprintf('Entity "%s" is not accessible', $entity->getFqcn()));
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction('edit')
            ->setEntityId($entity->getPrimaryKeyValue())
            ->generateUrl();

        return new RedirectResponse($url);
    }
}
