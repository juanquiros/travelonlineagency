<?php

namespace App\Form;

use App\Entity\Moneda;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonedaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre comercial',
            ])
            ->add('codigoIso', TextType::class, [
                'label' => 'Código ISO 4217',
                'attr' => [
                    'maxlength' => 3,
                    'style' => 'text-transform: uppercase;',
                ],
            ])
            ->add('simbolo', TextType::class, [
                'label' => 'Símbolo',
                'attr' => [
                    'maxlength' => 8,
                ],
            ])
            ->add('metodosPago', ChoiceType::class, [
                'label' => 'Medios de pago compatibles',
                'choices' => [
                    'Pago en efectivo' => Moneda::METODO_CASH,
                    'Mercado Pago' => Moneda::METODO_MERCADOPAGO,
                    'PayPal' => Moneda::METODO_PAYPAL,
                ],
                'multiple' => true,
                'expanded' => true,
                'help' => 'Podés habilitar varios medios para la misma moneda. Si no seleccionás ninguno, quedará disponible solo para pagos en efectivo.',
            ])
            ->add('habilitada', CheckboxType::class, [
                'label' => 'Habilitada',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Moneda::class,
        ]);
    }
}
