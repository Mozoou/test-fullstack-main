<?php

namespace App\Form;

use App\Dto\CollaboratorClockingDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollaboratorClockingType extends AbstractType
{
    private const INPUT_CLASS = 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2';
    private const LABEL_CLASS = 'block text-sm font-medium text-gray-700 mb-1';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label'      => 'Date',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'widget'     => 'single_text',
                'attr'       => ['class' => self::INPUT_CLASS],
            ])
            ->add('projects', CollectionType::class, [
                'entry_type'    => ProjectDurationType::class,
                'entry_options' => [],
                'label'         => 'Chantiers et durées',
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Créer mon pointage',
                'attr'  => ['class' => 'w-full inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CollaboratorClockingDTO::class]);
    }
}
