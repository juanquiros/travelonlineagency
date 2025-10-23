<?php

namespace App\Form;

use App\Entity\Destino;
use App\Entity\DestinoCategoria;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DestinoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del destino',
                'constraints' => [
                    new NotBlank(message: 'Ingresá el nombre del destino.'),
                    new Length(max: 180, maxMessage: 'El nombre no puede superar los {{ limit }} caracteres.'),
                ],
                'attr' => [
                    'maxlength' => 180,
                ],
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'constraints' => [
                    new NotBlank(message: 'Ingresá la dirección.'),
                    new Length(max: 255, maxMessage: 'La dirección no puede superar los {{ limit }} caracteres.'),
                ],
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('categoria', EntityType::class, [
                'class' => DestinoCategoria::class,
                'choice_label' => 'nombre',
                'label' => 'Categoría',
                'placeholder' => 'Seleccioná una categoría',
                'constraints' => [
                    new NotBlank(message: 'Seleccioná una categoría.'),
                ],
            ])
            ->add('descripcionCorta', TextareaType::class, [
                'label' => 'Descripción breve',
                'attr' => ['rows' => 3],
                'constraints' => [
                    new NotBlank(message: 'Ingresá una descripción breve.'),
                    new Length(max: 255, maxMessage: 'La descripción breve no puede superar los {{ limit }} caracteres.'),
                ],
            ])
            ->add('descripcionDetallada', TextareaType::class, [
                'label' => 'Descripción detallada',
                'attr' => [
                    'data-controller' => 'tinymce',
                    'data-tinymce-toolbar-value' => 'undo redo | styleselect | bold italic | bullist numlist | link image | alignleft aligncenter alignright',
                    'data-tinymce-height-value' => '320',
                ],
                'constraints' => [
                    new NotBlank(message: 'Completá la descripción detallada.'),
                ],
            ])
            ->add('coordenadasLat', NumberType::class, [
                'label' => 'Latitud',
                'required' => false,
                'scale' => 6,
                'html5' => true,
                'attr' => ['step' => 'any', 'data-destino-map-target' => 'lat'],
            ])
            ->add('coordenadasLng', NumberType::class, [
                'label' => 'Longitud',
                'required' => false,
                'scale' => 6,
                'html5' => true,
                'attr' => ['step' => 'any', 'data-destino-map-target' => 'lng'],
            ])
            ->add('imagenPrincipalUpload', FileType::class, [
                'label' => 'Imagen principal',
                'required' => false,
                'mapped' => false,
                'help' => 'Subí una imagen representativa en formato JPG o PNG (máx 2MB).',
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'La imagen debe ser JPG o PNG.',
                    ]),
                ],
            ])
            ->add('activo', CheckboxType::class, [
                'label' => 'Publicado',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Destino::class,
        ]);
    }
}
