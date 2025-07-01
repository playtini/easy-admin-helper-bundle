<?php

/** @noinspection UnknownInspectionInspection */

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use Playtini\EasyAdminHelperBundle\Entity\Interfaces\NameEntityInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/**
 * @property AdminUrlGenerator $adminUrlGenerator
 */
trait InlineEditCrudControllerTrait
{
    /** @noinspection HtmlUnknownTarget */
    private function formatEdit(NameEntityInterface $v): string
    {
        $url = $this->adminUrlGenerator
            ->unsetAllExcept('menuIndex')
            ->setController(static::class)
            ->setAction(Action::EDIT)
            ->setEntityId($v->getId())
            ->generateUrl();


        return sprintf('<a href="%s">%s</a>', $url, $v->getName());
    }
}
