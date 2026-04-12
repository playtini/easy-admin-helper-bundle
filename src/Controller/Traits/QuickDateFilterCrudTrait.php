<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use Doctrine\ORM\QueryBuilder;
use Gupalo\DateUtils\Dat;

trait QuickDateFilterCrudTrait
{
    protected function applyQuickDateFilter(QueryBuilder $qb): QueryBuilder
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $period = $request?->query->getString('period', $this->getQuickDateDefault()) ?? $this->getQuickDateDefault();

        $field = 'entity.' . $this->getQuickDateField();

        $since = match ($period) {
            '1h' => Dat::subHours(1),
            'today' => Dat::today(),
            '24h' => Dat::subHours(24),
            '7d' => Dat::subDays(7),
            '30d' => Dat::subDays(30),
            default => null,
        };

        if ($since !== null) {
            $qb->andWhere(sprintf('%s >= :periodSince', $field))
                ->setParameter('periodSince', $since);
        }

        return $qb;
    }

    protected function getQuickDateField(): string
    {
        return 'createdAt';
    }

    protected function getQuickDateDefault(): string
    {
        return '24h';
    }

    /**
     * @return array<string, string>
     */
    protected function getQuickDateOptions(): array
    {
        return [
            '1h' => 'Last hour',
            'today' => 'Today',
            '24h' => '24h',
            '7d' => '7 days',
            '30d' => '30 days',
        ];
    }
}
