<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Lenguaje;
use Doctrine\DBAL\Types\ArrayType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre')
            ->add('descripcion')
            ->add('detalles')
            ->add('form_requerido',HiddenType::class)
            ->add('imagenes',HiddenType::class)
            ->add('fechasdelservicio',HiddenType::class)

            ->add('horaprevia',NumberType::class,['label'=>'Horas antes del cierre'])
            ->add('disponibles',NumberType::class,['label'=>'Cantidad disponible'])
            ->add('validoHasta', DateTimeType::class, [
                'widget' => 'single_text',
                'required'=>false
            ])
            ->add('preciosaux',HiddenType::class,['required'=>false,'mapped' => false])
            ->add('habilitado', CheckboxType::class,['required'=>false])
            ->add('lenguaje', EntityType::class, [
                'label'=>'Lenguaje por defecto',
                'attr'=>['class'=>'form-control'],
                'class' => Lenguaje::class,
                'choice_label' => 'nombre',
            ])
            ->add('Guardar',SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
