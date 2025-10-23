<?php

namespace App\Form;

use App\Entity\DestinoCategoria;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DestinoFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', TextType::class, [
                'required' => false,
                'label' => 'Buscar',
                'attr' => ['placeholder' => 'Nombre o dirección'],
            ])
            ->add('categoria', EntityType::class, [
                'class' => DestinoCategoria::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Todas las categorías',
                'required' => false,
                'label' => 'Categoría',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
