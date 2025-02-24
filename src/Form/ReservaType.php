<?php

namespace App\Form;

use App\Entity\Reserva;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre')
            ->add('apellido')
            ->add('documento')
            ->add('email',EmailType::class)
            ->add('telefono')
            ->add('Reservar', SubmitType::class,['label'=>'Seguir al pago','attr'=>['class'=>'btn btn-primary w-100 mb-5']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reserva::class,
        ]);
    }
}
