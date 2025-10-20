<?php

namespace App\Form;

use App\Entity\DriverProfile;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferAssignDriverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('driver', EntityType::class, [
                'class' => DriverProfile::class,
                'choice_label' => function (DriverProfile $driver) {
                    return sprintf('%s (%s)', $driver->getNombreCompleto(), $driver->getPatente());
                },
                'label' => 'Chofer habilitado',
                'query_builder' => $options['query_builder'],
            ])
            ->add('notas', TextareaType::class, [
                'label' => 'Notas para el chofer (opcional)',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'query_builder' => null,
        ]);
        $resolver->setAllowedTypes('query_builder', ['null', 'callable']);
    }
}
