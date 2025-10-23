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

class DestinoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del destino',
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
            ])
            ->add('categoria', EntityType::class, [
                'class' => DestinoCategoria::class,
                'choice_label' => 'nombre',
                'label' => 'Categoría',
                'placeholder' => 'Seleccioná una categoría',
            ])
            ->add('descripcionCorta', TextareaType::class, [
                'label' => 'Descripción breve',
                'attr' => ['rows' => 3],
            ])
            ->add('descripcionDetallada', TextareaType::class, [
                'label' => 'Descripción detallada',
                'attr' => [
                    'data-controller' => 'tinymce',
                    'data-tinymce-toolbar-value' => 'undo redo | styleselect | bold italic | bullist numlist | link image | alignleft aligncenter alignright',
                    'data-tinymce-height-value' => '320',
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
