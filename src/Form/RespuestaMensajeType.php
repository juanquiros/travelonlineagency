<?php

namespace App\Form;


use App\Entity\RespuestaMensaje;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RespuestaMensajeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mensaje',TextType::class,['label'=>'Responder'])
            ->add('Enviar',SubmitType::class,['attr'=>['class'=>'btn btn-sm btn-primary']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RespuestaMensaje::class,
        ]);
    }
}
