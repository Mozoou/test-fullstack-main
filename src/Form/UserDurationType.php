<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class UserDurationType extends AbstractType
{
    private const INPUT_CLASS = 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn(?User $u) => $u ? $u->getLastName() . ' ' . $u->getFirstName() : null,
                'label'        => 'Collaborateur',
                'attr'         => ['class' => self::INPUT_CLASS],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotNull(),
                ],
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (h)',
                'attr'  => ['class' => self::INPUT_CLASS],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank(),
                    new \Symfony\Component\Validator\Constraints\LessThanOrEqual(['value' => 10, 'message' => 'La durée ne peut pas dépasser {{ compared_value }} heures.']),
                ],
            ]);
    }
}
