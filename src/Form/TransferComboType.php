<?php

namespace App\Form;

use App\Entity\Moneda;
use App\Entity\TransferCombo;
use App\Entity\TransferDestination;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
            ->add('moneda', EntityType::class, [
                'label' => 'Moneda del combo',
                'class' => Moneda::class,
                'choices' => $options['currency_choices'],
                'choice_label' => static function (Moneda $moneda): string {
                    $iso = $moneda->getCodigoIso() ?? $moneda->getSimbolo() ?? '';
                    return trim(sprintf('%s (%s)', $moneda->getNombre(), $iso));
                },
                'placeholder' => 'Seleccioná una moneda',
                'required' => false,
                'group_by' => static function (Moneda $moneda): string {
                    return match ($moneda->getMetodoPago()) {
                        Moneda::METODO_MERCADOPAGO => 'Mercado Pago',
                        Moneda::METODO_PAYPAL => 'PayPal',
                        default => 'Pago en efectivo',
                    };
                },
            ])
            ->add('precio', MoneyType::class, [
                'label' => 'Precio total',
                'currency' => $options['default_currency_code'] ?? 'ARS',
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
        ]);
        $resolver->setAllowedTypes('selected_destinations', 'array');
        $resolver->setAllowedTypes('currency_choices', 'array');
        $resolver->setAllowedTypes('default_currency_code', ['null', 'string']);
    }
}
