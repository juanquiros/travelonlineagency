<?php

namespace App\Form;

use App\Entity\Moneda;
use App\Entity\TransferDestination;
use App\Entity\TransferDestinationCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
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
            ->add('categoria', EntityType::class, [
                'label' => 'Categoría',
                'class' => TransferDestinationCategory::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccioná una categoría',
                'required' => true,
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ej: Ruta 12 km 5, Puerto Iguazú',
                ],
            ])
            ->add('descripcionCorta', TextareaType::class, [
                'label' => 'Descripción corta',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 255,
                ],
            ])
            ->add('descripcionDetallada', TextareaType::class, [
                'label' => 'Descripción detallada',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'data-controller' => 'tinymce',
                    'data-tinymce-plugins-value' => 'advlist autolink lists link image preview code fullscreen table autoresize',
                    'data-tinymce-toolbar-value' => 'undo redo | styles | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | removeformat code fullscreen',
                ],
            ])
            ->add('instagram', UrlType::class, [
                'label' => 'Instagram',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://instagram.com/tu-destino',
                ],
                'help' => 'Pegá la URL pública del perfil en Instagram.',
            ])
            ->add('x', UrlType::class, [
                'label' => 'Perfil en X (Twitter)',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://x.com/tu-destino',
                ],
                'help' => 'Opcional, se mostrará como ícono de X.',
            ])
            ->add('facebook', UrlType::class, [
                'label' => 'Facebook',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://facebook.com/tu-destino',
                ],
            ])
            ->add('whatsapp', TextType::class, [
                'label' => 'WhatsApp',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://wa.me/549XXXXXXXXX',
                ],
                'help' => 'Incluí el enlace completo generado por WhatsApp o un número con prefijo internacional.',
            ])
            ->add('sitioWeb', UrlType::class, [
                'label' => 'Sitio web oficial',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://www.ejemplo.com',
                ],
            ])
            ->add('moneda', EntityType::class, [
                'label' => 'Moneda de la tarifa',
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
            ->add('tarifaBase', MoneyType::class, [
                'label' => 'Tarifa base',
                'currency' => $options['default_currency_code'] ?? 'ARS',
                'divisor' => 1,
                'scale' => 2,
            ])
            ->add('imagenPortadaFile', FileType::class, [
                'label' => 'Imagen principal',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo del destino',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
                'help' => 'Idealmente un SVG o PNG transparente de al menos 200px de ancho.',
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
            ])
            ->add('destacadoInicio', CheckboxType::class, [
                'label' => 'Mostrar en destacados de la página de inicio',
                'required' => false,
            ])
            ->add('ordenDestacado', IntegerType::class, [
                'label' => 'Orden de destaque',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                ],
                'help' => 'Usá valores bajos para aparecer primero (0, 1, 2…).',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferDestination::class,
            'latitude' => null,
            'longitude' => null,
            'currency_choices' => [],
            'default_currency_code' => 'ARS',
        ]);
        $resolver->setAllowedTypes('latitude', ['null', 'float', 'string']);
        $resolver->setAllowedTypes('longitude', ['null', 'float', 'string']);
        $resolver->setAllowedTypes('currency_choices', 'array');
        $resolver->setAllowedTypes('default_currency_code', ['null', 'string']);
    }
}
