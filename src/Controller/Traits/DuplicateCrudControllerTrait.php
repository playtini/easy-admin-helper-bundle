<?php

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Gupalo\BrowserNotifier\BrowserNotifier;
use Playtini\EasyAdminHelperBundle\Entity\Interfaces\DuplicateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

/**
 * @property BrowserNotifier $browserNotifier
 * @property AdminUrlGenerator $adminUrlGenerator
 * @method RedirectResponse redirect(string $url, int $status = 302)
 */
trait DuplicateCrudControllerTrait
{
    use SaveCrudControllerTrait;

    public function duplicate(AdminContext $context): RedirectResponse
    {
        $item = $context->getEntity()->getInstance();

        if ($item instanceof DuplicateInterface) {
            $className = preg_replace('#^.*\\\\#', '', self::getEntityFqcn());

            try {
                $newItem = $item->duplicate($item);
                $this->save($newItem, true);
                $this->browserNotifier->success(sprintf('Duplicated %s "%s"', $className, $item));
            } catch (Throwable $e) {
                $this->browserNotifier->error($e->getMessage());
            }
        } else {
            $this->browserNotifier->warning('Item cannot be duplicated');
        }

        return $this->redirect(
            $this->adminUrlGenerator->get('referrer') ?:
                $this->adminUrlGenerator->setController(__CLASS__)->setAction(Action::INDEX)->removeReferrer()->generateUrl()
        );
    }

    public function createDuplicateAction(): Action
    {
        return Action::new('duplicate', '', 'fa-regular fa-clone')
            ->addCssClass('text-warning')
            ->linkToCrudAction('duplicate');
    }

    public function configureActions(Actions $actions): Actions
    {
        $duplicateAction = $this->createDuplicateAction();

        /** @noinspection PhpUndefinedClassInspection */
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $duplicateAction);
    }
}
