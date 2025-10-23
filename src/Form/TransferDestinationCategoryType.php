<?php

namespace App\Form;

use App\Entity\TransferDestinationCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferDestinationCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre de la categoría',
            ])
            ->add('icono', TextType::class, [
                'label' => 'Ícono HTML',
                'required' => false,
                'attr' => [
                    'placeholder' => '<i class="bi bi-tree"></i>',
                ],
            ])
            ->add('Guardar', SubmitType::class, [
                'label' => 'Guardar categoría',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferDestinationCategory::class,
        ]);
    }
}
