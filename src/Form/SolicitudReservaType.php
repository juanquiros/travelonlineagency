<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\SolicitudReserva;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SolicitudReservaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class,['required'=>true])
            ->add('surname',TextType::class,['required'=>true])
            ->add('email',EmailType::class,['required'=>true])
            ->add('phone',NumberType::class,['required'=>true])
            ->add('fechaSeleccionadaString',HiddenType::class,['required'=>false,'mapped' => false])
            ->add('form_required',HiddenType::class,['required'=>false,'empty_data' => '[]'])
            ->add('inChargeOf',HiddenType::class,['required'=>false,'empty_data' => '[]'])
            ->add('submit', SubmitType::class,['label'=>'Solicitar y Pagar','attr'=>['class'=>'btn btn-success w-100']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SolicitudReserva::class,
        ]);
    }
}
