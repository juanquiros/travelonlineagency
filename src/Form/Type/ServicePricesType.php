<?php

namespace App\Form\Type;

use App\Entity\Moneda;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServicePricesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currencies = $options['currencies'];
        $values = $options['values'];
        $defaultCode = $options['default_currency_code'] ?? 'ARS';

        foreach ($currencies as $currency) {
            if (!$currency instanceof Moneda) {
                continue;
            }

            $fieldName = sprintf('currency_%d', $currency->getId());
            $iso = $currency->getCodigoIso() ?? $currency->getSimbolo() ?? $defaultCode;
            $builder->add($fieldName, MoneyType::class, [
                'label' => sprintf('%s (%s)', $currency->getNombre(), strtoupper(substr($iso, 0, 3))),
                'mapped' => false,
                'required' => false,
                'currency' => strtoupper(substr($iso, 0, 3)),
                'divisor' => 1,
                'scale' => 2,
                'data' => $values[$currency->getId()] ?? null,
                'attr' => [
                    'placeholder' => 'Ingresá un monto',
                ],
                'help' => $this->buildMethodLabel($currency),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'currencies' => [],
            'values' => [],
            'default_currency_code' => 'ARS',
        ]);

        $resolver->setAllowedTypes('currencies', 'array');
        $resolver->setAllowedTypes('values', 'array');
        $resolver->setAllowedTypes('default_currency_code', ['null', 'string']);
    }

    private function buildMethodLabel(Moneda $moneda): ?string
    {
        $metodos = $moneda->getMetodosPago();
        if ($metodos === []) {
            return null;
        }

        $labels = array_map(static function (string $metodo): string {
            return match ($metodo) {
                Moneda::METODO_MERCADOPAGO => 'Compatible con Mercado Pago',
                Moneda::METODO_PAYPAL => 'Compatible con PayPal',
                default => 'Disponible para pago en efectivo',
            };
        }, $metodos);

        return implode(' · ', array_unique($labels));
    }
}
