<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Gupalo\DateUtils\Dat;

/**
 * Adds date range picker filtering to a CRUD controller.
 *
 * JS/CSS assets are shipped with the bundle (public/js/, public/css/).
 *
 * Query params: ?from=YYYY-MM-DD&to=YYYY-MM-DD
 *
 * Usage:
 *   1. Use this trait in your CRUD controller
 *   2. Override template: ->overrideTemplate('crud/index', '@EasyAdminHelper/crud/date_range_picker_index.html.twig')
 *   3. Call parent::configureResponseParameters() and parent::createIndexQueryBuilder() if overriding those methods
 */
trait DateRangePickerCrudTrait
{
    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $responseParameters = parent::configureResponseParameters($responseParameters);

        if (Crud::PAGE_INDEX === $responseParameters->get('pageName')) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
            $responseParameters->set('from', $request?->query->getString('from', ''));
            $responseParameters->set('to', $request?->query->getString('to', ''));
        }

        return $responseParameters;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        return $this->applyDateRangeFilter($qb, $searchDto);
    }

    protected function applyDateRangeFilter(QueryBuilder $qb, ?SearchDto $searchDto = null): QueryBuilder
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $from = $request?->query->getString('from', '');
        $to = $request?->query->getString('to', '');

        $field = 'entity.' . $this->getDateRangeField();

        if ($from !== '' || $to !== '') {
            if ($from !== '') {
                $qb->andWhere(sprintf('%s >= :drMinDate', $field))
                    ->setParameter('drMinDate', Dat::create($from));
            }
            if ($to !== '') {
                $qb->andWhere(sprintf('%s < :drMaxDate', $field))
                    ->setParameter('drMaxDate', Dat::create($to . ' +1 day'));
            }
        } elseif ($searchDto === null || !isset($searchDto->getAppliedFilters()[$this->getDateRangeField()])) {
            $qb->andWhere(sprintf('%s >= :drDefaultFrom', $field))
                ->setParameter('drDefaultFrom', Dat::subDays($this->getDateRangeDefaultDays()));
        }

        return $qb;
    }

    protected function getDateRangeField(): string
    {
        return 'createdAt';
    }

    protected function getDateRangeDefaultDays(): int
    {
        return 7;
    }
}
