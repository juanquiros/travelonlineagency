<?php

namespace App\Form;

use App\Entity\TransferFormField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferFormFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clave', TextType::class, [
                'label' => 'Clave interna',
                'help' => 'Sin espacios ni caracteres especiales.',
            ])
            ->add('etiqueta', TextType::class, [
                'label' => 'Etiqueta visible',
            ])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo de dato',
                'choices' => [
                    'Texto corto' => TransferFormField::TYPE_TEXT,
                    'Área de texto' => TransferFormField::TYPE_TEXTAREA,
                    'Correo electrónico' => TransferFormField::TYPE_EMAIL,
                    'Teléfono' => TransferFormField::TYPE_PHONE,
                    'Fecha y hora' => TransferFormField::TYPE_DATETIME,
                    'Número' => TransferFormField::TYPE_NUMBER,
                ],
            ])
            ->add('requerido', CheckboxType::class, [
                'label' => 'Campo obligatorio',
                'required' => false,
            ])
            ->add('orden', IntegerType::class, [
                'label' => 'Orden en el formulario',
            ])
            ->add('opciones', TextareaType::class, [
                'label' => 'Opciones (JSON opcional)',
                'required' => false,
                'mapped' => false,
                'data' => $options['initial_options'],
                'attr' => ['rows' => 3],
                'help' => 'Para campos con opciones personalizadas.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferFormField::class,
            'initial_options' => '',
        ]);
        $resolver->setAllowedTypes('initial_options', 'string');
    }
}
