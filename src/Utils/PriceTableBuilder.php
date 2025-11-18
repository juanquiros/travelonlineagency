<?php

namespace App\Utils;

use App\Entity\Moneda;
use App\Entity\Precio;

final class PriceTableBuilder
{
    /**
     * @param iterable<Precio> $precios
     * @return array<int, array{iso: string, label: string, symbol: string, amount: float}>
     */
    public static function fromPrecios(iterable $precios, ?Moneda $fallbackCurrency, ?float $fallbackAmount): array
    {
        $rows = [];

        foreach ($precios as $precio) {
            if (!$precio instanceof Precio) {
                continue;
            }

            $currency = $precio->getMoneda();
            if (!$currency instanceof Moneda) {
                continue;
            }

            $iso = strtoupper((string) ($currency->getCodigoIso() ?? $currency->getSimbolo() ?? ''));
            if ($iso === '') {
                continue;
            }

            $rows[$iso] = [
                'iso' => $iso,
                'label' => $currency->getNombre() ?? $iso,
                'symbol' => $currency->getSimbolo() ?? $iso,
                'amount' => (float) $precio->getValor(),
            ];
        }

        if ($fallbackCurrency instanceof Moneda && $fallbackAmount !== null) {
            $iso = strtoupper((string) ($fallbackCurrency->getCodigoIso() ?? $fallbackCurrency->getSimbolo() ?? ''));
            if ($iso !== '' && !array_key_exists($iso, $rows)) {
                $rows[$iso] = [
                    'iso' => $iso,
                    'label' => $fallbackCurrency->getNombre() ?? $iso,
                    'symbol' => $fallbackCurrency->getSimbolo() ?? $iso,
                    'amount' => (float) $fallbackAmount,
                ];
            }
        }

        ksort($rows);

        return array_values($rows);
    }
}
