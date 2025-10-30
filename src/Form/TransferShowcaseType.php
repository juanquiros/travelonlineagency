<?php

namespace App\Form;

use App\Entity\TransferShowcase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TransferShowcaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titulo', TextType::class, [
                'label' => 'Título',
                'constraints' => [
                    new NotBlank(['message' => 'Ingresá un título para el destacado.']),
                    new Length(['max' => 180]),
                ],
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción breve',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 255,
                ],
            ])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo de pieza',
                'choices' => [
                    'Imagen' => TransferShowcase::TYPE_IMAGE,
                    'Video de YouTube' => TransferShowcase::TYPE_VIDEO,
                ],
                'expanded' => false,
            ])
            ->add('imagenFile', FileType::class, [
                'label' => 'Imagen (JPG o PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Subí una imagen JPG o PNG válida (máx. 4MB).',
                    ]),
                ],
            ])
            ->add('videoUrl', UrlType::class, [
                'label' => 'URL de YouTube',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://www.youtube.com/watch?v=XXXX',
                ],
            ])
            ->add('destacado', CheckboxType::class, [
                'label' => 'Mostrar en la página de inicio',
                'required' => false,
            ])
            ->add('posicion', IntegerType::class, [
                'label' => 'Orden de aparición',
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferShowcase::class,
        ]);
    }
}
