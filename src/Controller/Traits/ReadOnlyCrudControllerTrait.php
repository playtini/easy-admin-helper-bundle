<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;

/**
 * @method iterable<FieldInterface> doConfigureFields(string $pageName)
 */
trait ReadOnlyCrudControllerTrait
{
    use CrudControllerTrait;

    public function configureActions(Actions $actions): Actions
    {
        return $this->_configureReadOnlyActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        if (!method_exists($this, 'doConfigureFields')) {
            die(sprintf('error: implement %s->doConfigureFields', static::class));
        }

        $items = $this->doConfigureFields($pageName);
        foreach ($items as $item) {
            if (method_exists($item, 'setDisabled')) {
                $item->setDisabled();
            }
            if (method_exists($item, 'setRequired')) {
                $item->setRequired(false);
            }

            yield $item;
        }
    }
}
