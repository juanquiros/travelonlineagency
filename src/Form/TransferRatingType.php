<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TransferRatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'label' => 'Calificación',
                'choices' => [
                    '5 - Excelente' => 5,
                    '4 - Muy bueno' => 4,
                    '3 - Bueno' => 3,
                    '2 - Regular' => 2,
                    '1 - Necesita mejorar' => 1,
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Seleccioná una calificación para tu traslado.']),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Contanos tu experiencia',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => '¿Qué fue lo que más te gustó de tu traslado? ¿Hay algo que podamos mejorar?',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Escribí un breve comentario sobre tu traslado.']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'El comentario debe tener al menos {{ limit }} caracteres.',
                        'max' => 800,
                        'maxMessage' => 'El comentario debe tener menos de {{ limit }} caracteres.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
