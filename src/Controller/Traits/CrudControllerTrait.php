<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

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
}
