<?php

namespace App\Form;

use App\Entity\TransferDestination;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferDestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del destino',
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'data-controller' => 'tinymce',
                    'data-tinymce-plugins-value' => 'advlist autolink lists link image preview code fullscreen table autoresize',
                    'data-tinymce-toolbar-value' => 'undo redo | styles | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | removeformat code fullscreen',
                ],
            ])
            ->add('tarifaBase', MoneyType::class, [
                'label' => 'Tarifa base',
                'currency' => 'ARS',
                'divisor' => 1,
                'scale' => 2,
            ])
            ->add('imagenPortadaFile', FileType::class, [
                'label' => 'Imagen de portada',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
            ])
            ->add('latitud', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'data' => $options['latitude'],
                'attr' => [
                    'data-transfer-destination-map-target' => 'latitude',
                ],
            ])
            ->add('longitud', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'data' => $options['longitude'],
                'attr' => [
                    'data-transfer-destination-map-target' => 'longitude',
                ],
            ])
            ->add('activo', CheckboxType::class, [
                'label' => 'Disponible para traslados',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferDestination::class,
            'latitude' => null,
            'longitude' => null,
        ]);
        $resolver->setAllowedTypes('latitude', ['null', 'float', 'string']);
        $resolver->setAllowedTypes('longitude', ['null', 'float', 'string']);
    }
}
