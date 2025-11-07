<?php

namespace App\Form;

use App\Entity\TransferCombo;
use App\Entity\TransferDestination;
use App\Form\Type\ServicePricesType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferComboType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del combo',
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'required' => true,
                'attr' => [
                    'rows' => 6,
                    'data-controller' => 'tinymce',
                    'data-tinymce-plugins-value' => 'advlist autolink lists link image preview code fullscreen table autoresize',
                    'data-tinymce-toolbar-value' => 'undo redo | styles | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | removeformat code fullscreen',
                ],
            ])
            ->add('prices', ServicePricesType::class, [
                'label' => 'Tarifas por moneda',
                'currencies' => $options['currency_choices'],
                'values' => $options['price_values'],
                'default_currency_code' => $options['default_currency_code'],
            ])
            ->add('imagenPortadaFile', FileType::class, [
                'label' => 'Imagen de portada',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
            ])
            ->add('destinos', EntityType::class, [
                'class' => TransferDestination::class,
                'choice_label' => 'nombre',
                'multiple' => true,
                'expanded' => false,
                'mapped' => false,
                'label' => 'Destinos incluidos (orden según selección)',
                'required' => false,
                'data' => $options['selected_destinations'],
            ])
            ->add('activo', CheckboxType::class, [
                'label' => 'Combo visible',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferCombo::class,
            'selected_destinations' => [],
            'currency_choices' => [],
            'default_currency_code' => 'ARS',
            'price_values' => [],
        ]);
        $resolver->setAllowedTypes('selected_destinations', 'array');
        $resolver->setAllowedTypes('currency_choices', 'array');
        $resolver->setAllowedTypes('default_currency_code', ['null', 'string']);
        $resolver->setAllowedTypes('price_values', 'array');
    }
}
