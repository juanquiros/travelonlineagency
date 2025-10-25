<?php

namespace App\Form;

use App\Entity\BootstrapIcon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BootstrapIconType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre descriptivo',
            ])
            ->add('cssClass', TextType::class, [
                'label' => 'Clase de Bootstrap Icons',
                'attr' => [
                    'placeholder' => 'bi-tree',
                ],
                'help' => 'Ingresá la clase tal como figura en la librería de Bootstrap Icons (por ejemplo, <code>bi-compass</code>).',
                'help_html' => true,
            ])
            ->add('Guardar', SubmitType::class, [
                'label' => 'Guardar icono',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BootstrapIcon::class,
        ]);
    }
}
