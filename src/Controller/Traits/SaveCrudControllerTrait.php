<?php

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use InvalidArgumentException;

/**
 * @method static string getEntityFqcn()
 * @method ServiceEntityRepository getRepository()
 */
trait SaveCrudControllerTrait
{
    private function save($entity, bool $flush = false): void
    {
        if (get_class($entity) !== self::getEntityFqcn()) {
            throw new InvalidArgumentException(sprintf('cannot_save "%s", expected "%s"', get_class($entity), self::getEntityFqcn()));
        }

        if (!method_exists($this->getRepository(), 'save')) {
            die(sprintf('error: implement %s->save', get_class($this->getRepository())));
        }

        // @phpstan-ignore-next-line
        $this->getRepository()->save($entity, $flush);
    }
}
