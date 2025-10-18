<?php

namespace App\Form;

use App\Entity\TransferCombo;
use App\Entity\TransferDestination;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferComboType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del combo',
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('precio', MoneyType::class, [
                'label' => 'Precio total',
                'currency' => 'ARS',
                'divisor' => 1,
                'scale' => 2,
            ])
            ->add('imagenPortada', TextType::class, [
                'label' => 'Imagen de portada (URL relativa)',
                'required' => false,
            ])
            ->add('destinos', EntityType::class, [
                'class' => TransferDestination::class,
                'choice_label' => 'nombre',
                'multiple' => true,
                'expanded' => false,
                'mapped' => false,
                'label' => 'Destinos incluidos (orden según selección)',
                'required' => false,
                'data' => $options['selected_destinations'],
            ])
            ->add('activo', CheckboxType::class, [
                'label' => 'Combo visible',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferCombo::class,
            'selected_destinations' => [],
        ]);
        $resolver->setAllowedTypes('selected_destinations', 'array');
    }
}
