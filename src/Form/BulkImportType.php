<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Form;

use Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Paste-a-TSV bulk import form. Deliberately knows nothing about what is being
 * imported: the controller reads {@see BulkImport::getRows()} and decides what
 * each row means.
 */
class BulkImportType extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('data', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'rows' => 20,
                    'wrap' => 'off',
                ],
                'help' => 'TSV',
            ])
            ->add('mode', ChoiceType::class, [
                'choices' => [
                    'Create or update' => BulkImport::MODE_CREATE_OR_UPDATE,
                    'Create only (error if exists)' => BulkImport::MODE_CREATE_ONLY,
                    'Update only (error if missing)' => BulkImport::MODE_UPDATE_ONLY,
                    'Create and skip existing' => BulkImport::MODE_CREATE_SKIP_EXISTING,
                    'Update and skip missing' => BulkImport::MODE_UPDATE_SKIP_MISSING,
                ],
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Import',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BulkImport::class,
        ]);
    }
}
