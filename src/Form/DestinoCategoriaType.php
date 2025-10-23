<?php

namespace App\Form;

use App\Entity\DestinoCategoria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DestinoCategoriaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
            ])
            ->add('icono', TextareaType::class, [
                'label' => 'Ícono (HTML)',
                'attr' => [
                    'rows' => 2,
                    'placeholder' => '<i class="bi bi-tree"></i>',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DestinoCategoria::class,
        ]);
    }
}
