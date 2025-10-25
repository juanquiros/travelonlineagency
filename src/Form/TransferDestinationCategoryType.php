<?php

namespace App\Form;

use App\Entity\BootstrapIcon;
use App\Entity\TransferDestinationCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
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
            ->add('iconDefinition', EntityType::class, [
                'label' => 'Ícono de la biblioteca',
                'class' => BootstrapIcon::class,
                'choice_label' => fn (BootstrapIcon $icon) => sprintf('%s (%s)', $icon->getNombre(), $icon->getCssClass()),
                'placeholder' => 'Seleccionar ícono',
                'required' => false,
                'help' => 'Gestioná la biblioteca desde el panel de iconos de destinos.',
            ])
            ->add('color', ColorType::class, [
                'label' => 'Color de referencia',
                'required' => false,
                'help' => 'Elegí un color para diferenciar esta categoría en el mapa.',
                'attr' => [
                    'class' => 'form-control form-control-color w-100',
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
