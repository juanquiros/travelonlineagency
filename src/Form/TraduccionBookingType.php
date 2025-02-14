<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Lenguaje;
use App\Entity\TraduccionBooking;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TraduccionBookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre')
            ->add('descripcion')
            ->add('detalles')
            ->add('lenguaje', EntityType::class, [
                'class' => Lenguaje::class,
                'attr'=>[
                    'disabled'=>true
                ],
                'choice_label' => 'nombre',
            ])
            ->add('Guardar',SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TraduccionBooking::class,
        ]);
    }
}
