<?php

namespace App\Services;

use App\Entity\Booking;
use App\Entity\BookingPartner;
use App\Entity\CredencialesMercadoPago;
use App\Entity\CredencialesPayPal;
use App\Entity\Plataforma;
use App\Entity\Precio;
use App\Entity\SolicitudReserva;
use App\Entity\TransferRequest;
use App\Entity\Moneda;
use Doctrine\ORM\EntityManagerInterface;

class PaymentOptionsResolver
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBookingOptions(SolicitudReserva $reserva, Plataforma $plataforma): array
    {
        $booking = $reserva->getBooking();
        if (!$booking instanceof Booking) {
            return [];
        }

        $cantidad = $this->getBookingPassengerCount($reserva);

        $options = [];

        if ($plataforma->isEnableMercadoPagoPayments()) {
            $mercadoPagoCurrency = $this->findCurrencyForMethod(Moneda::METODO_MERCADOPAGO);
            $precio = $mercadoPagoCurrency ? $this->findBookingPrice($booking, $mercadoPagoCurrency->getId()) : null;
            $credencial = $this->resolveMercadoPagoCredentials($booking->getBookingPartner(), $plataforma);
            $credencialesValidas = $this->hasValidMercadoPagoCredentials($credencial);
            $total = ($precio instanceof Precio) ? (float) $precio->getValor() * $cantidad : null;

            if (
                $mercadoPagoCurrency &&
                $precio &&
                $credencial &&
                $credencialesValidas &&
                $total !== null &&
                $total > 0
            ) {
                $iso = $this->resolveCurrencyIso($mercadoPagoCurrency);
                $options[] = [
                    'type' => 'mercadopago',
                    'label' => sprintf('Mercado Pago (%s)', $iso),
                    'currency' => $iso,
                    'displayCurrency' => $mercadoPagoCurrency->getNombre() ?? $iso,
                    'total' => $total,
                    'route' => 'mercadopago_pay_booking',
                    'params' => ['id' => $reserva->getId()],
                    'available' => true,
                ];
            } else {
                $options[] = $this->buildUnavailableOption(
                    'mercadopago',
                    $mercadoPagoCurrency,
                    $this->describeUnavailableBookingReason($mercadoPagoCurrency, $precio, $credencialesValidas, $total)
                );
            }
        }

        if ($plataforma->isEnablePayPalPayments()) {
            $paypalCurrency = $this->findCurrencyForMethod(Moneda::METODO_PAYPAL);
            $precioPaypal = $paypalCurrency ? $this->findBookingPrice($booking, $paypalCurrency->getId()) : null;
            $credenciales = $plataforma->getCredencialesPayPal();
            $credencialesValidas = $this->hasValidPayPalCredentials($credenciales);
            $totalPaypal = ($precioPaypal instanceof Precio) ? (float) $precioPaypal->getValor() * $cantidad : null;

            if (
                $paypalCurrency &&
                $precioPaypal &&
                $credencialesValidas &&
                $totalPaypal !== null &&
                $totalPaypal > 0
            ) {
                $iso = $this->resolveCurrencyIso($paypalCurrency);
                $options[] = [
                    'type' => 'paypal',
                    'label' => sprintf('PayPal (%s)', $iso),
                    'currency' => $iso,
                    'displayCurrency' => $paypalCurrency->getNombre() ?? $iso,
                    'total' => $totalPaypal,
                    'route' => 'paypal_pay_booking',
                    'params' => ['id' => $reserva->getId()],
                    'available' => true,
                ];
            } else {
                $options[] = $this->buildUnavailableOption(
                    'paypal',
                    $paypalCurrency,
                    $this->describeUnavailableBookingReason($paypalCurrency, $precioPaypal, $credencialesValidas, $totalPaypal)
                );
            }
        }

        if ($plataforma->isEnableCashPayments()) {
            $cashCurrencies = $this->findEnabledCurrenciesForMethod(Moneda::METODO_CASH);
            $preferred = $plataforma->getMonedaDef();
            if ($preferred instanceof Moneda && $preferred->getMetodoPago() === Moneda::METODO_CASH) {
                array_unshift($cashCurrencies, $preferred);
            }
            $cashCurrencies = $this->uniqueCurrencies($cashCurrencies);

            $cashCurrency = null;
            $cashPrice = null;
            foreach ($cashCurrencies as $candidate) {
                $candidatePrice = $this->findBookingPrice($booking, $candidate->getId());
                if ($candidatePrice instanceof Precio) {
                    $cashCurrency = $candidate;
                    $cashPrice = $candidatePrice;
                    break;
                }
            }

            if (!$cashCurrency || !$cashPrice) {
                foreach ($booking->getPrecios() as $precio) {
                    if ($precio->getMoneda() instanceof Moneda) {
                        $cashCurrency = $precio->getMoneda();
                        $cashPrice = $precio;
                        break;
                    }
                }
            }

            if ($cashCurrency && $cashPrice) {
                $totalCash = (float) $cashPrice->getValor() * $cantidad;
                if ($totalCash > 0) {
                    $iso = $this->resolveCurrencyIso($cashCurrency);
                    $options[] = [
                        'type' => 'cash',
                        'label' => sprintf('Pago en efectivo (%s)', $iso),
                        'currency' => $iso,
                        'displayCurrency' => $cashCurrency->getNombre() ?? $iso,
                        'total' => $totalCash,
                        'route' => 'cash_pay_booking',
                        'params' => ['id' => $reserva->getId()],
                        'instructions' => $plataforma->getCashPaymentInstructions(),
                        'available' => true,
                    ];
                } else {
                    $options[] = $this->buildUnavailableOption(
                        'cash',
                        $cashCurrency,
                        'No pudimos calcular un importe válido para el pago en efectivo.'
                    );
                }
            } else {
                $options[] = $this->buildUnavailableOption(
                    'cash',
                    $cashCurrency,
                    'No encontramos una tarifa disponible para el pago en efectivo.'
                );
            }
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTransferOptions(TransferRequest $solicitud, Plataforma $plataforma): array
    {
        $options = [];
        $total = (float) $solicitud->getPrecioTotal();
        if ($total <= 0) {
            return $options;
        }

        $requestCurrencyIso = strtoupper($solicitud->getMoneda());
        $requestCurrency = $this->findCurrencyByIso($requestCurrencyIso);

        if ($plataforma->isEnableMercadoPagoPayments()) {
            $credencial = $plataforma->getCredencialesMercadoPago();
            $mercadoPagoCurrency = $this->findCurrencyForMethod(Moneda::METODO_MERCADOPAGO);
            $credencialesValidas = $this->hasValidMercadoPagoCredentials($credencial);
            $mercadoPagoIso = $mercadoPagoCurrency ? $this->resolveCurrencyIso($mercadoPagoCurrency) : null;

            if (
                $mercadoPagoCurrency &&
                $mercadoPagoIso === $requestCurrencyIso &&
                $credencial &&
                $credencialesValidas
            ) {
                $options[] = [
                    'type' => 'mercadopago',
                    'label' => sprintf('Mercado Pago (%s)', $requestCurrencyIso),
                    'currency' => $requestCurrencyIso,
                    'displayCurrency' => $mercadoPagoCurrency->getNombre() ?? $requestCurrencyIso,
                    'total' => $total,
                    'route' => 'mercadopago_pay_transfer',
                    'params' => ['id' => $solicitud->getId()],
                    'available' => true,
                ];
            } else {
                $options[] = $this->buildUnavailableOption(
                    'mercadopago',
                    $mercadoPagoCurrency,
                    $this->describeUnavailableTransferReason($requestCurrencyIso, $mercadoPagoCurrency, $credencialesValidas)
                );
            }
        }

        if ($plataforma->isEnablePayPalPayments()) {
            $paypalCurrency = $this->findCurrencyForMethod(Moneda::METODO_PAYPAL);
            $credenciales = $plataforma->getCredencialesPayPal();
            $credencialesValidas = $this->hasValidPayPalCredentials($credenciales);
            $paypalIso = $paypalCurrency ? $this->resolveCurrencyIso($paypalCurrency) : null;

            if (
                $paypalCurrency &&
                $paypalIso === $requestCurrencyIso &&
                $credencialesValidas
            ) {
                $options[] = [
                    'type' => 'paypal',
                    'label' => sprintf('PayPal (%s)', $requestCurrencyIso),
                    'currency' => $requestCurrencyIso,
                    'displayCurrency' => $paypalCurrency->getNombre() ?? $requestCurrencyIso,
                    'total' => $total,
                    'route' => 'paypal_pay_transfer',
                    'params' => ['id' => $solicitud->getId()],
                    'available' => true,
                ];
            } else {
                $options[] = $this->buildUnavailableOption(
                    'paypal',
                    $paypalCurrency,
                    $this->describeUnavailableTransferReason($requestCurrencyIso, $paypalCurrency, $credencialesValidas)
                );
            }
        }

        if ($plataforma->isEnableCashPayments()) {
            $options[] = [
                'type' => 'cash',
                'label' => sprintf('Pago en efectivo (%s)', $requestCurrencyIso),
                'currency' => $requestCurrencyIso,
                'displayCurrency' => $requestCurrency?->getNombre() ?? $requestCurrencyIso,
                'total' => $total,
                'route' => 'cash_pay_transfer',
                'params' => ['id' => $solicitud->getId()],
                'instructions' => $plataforma->getCashPaymentInstructions(),
                'available' => true,
            ];
        }

        return $options;
    }

    private function getBookingPassengerCount(SolicitudReserva $reserva): int
    {
        return $reserva->getPassengerCount();
    }

    private function findBookingPrice(Booking $booking, ?int $monedaId): ?Precio
    {
        if (!$monedaId) {
            return null;
        }

        foreach ($booking->getPrecios() as $precio) {
            if ($precio->getMoneda()?->getId() === $monedaId) {
                return $precio;
            }
        }

        return null;
    }

    private function resolveMercadoPagoCredentials(?BookingPartner $partner, Plataforma $plataforma): ?CredencialesMercadoPago
    {
        $partnerCredentials = $partner?->getMercadoPagoCuenta();
        if ($partnerCredentials instanceof CredencialesMercadoPago) {
            return $partnerCredentials;
        }

        return $plataforma->getCredencialesMercadoPago();
    }

    private function hasValidMercadoPagoCredentials(?CredencialesMercadoPago $credencial): bool
    {
        if (!$credencial) {
            return false;
        }

        return (bool) ($credencial->getAccessToken() || $credencial->getRefreshToken());
    }

    private function hasValidPayPalCredentials(?CredencialesPayPal $credenciales): bool
    {
        if (!$credenciales) {
            return false;
        }

        return (bool) ($credenciales->getClientId() && $credenciales->getClientSecret());
    }

    private function findCurrencyForMethod(string $method): ?Moneda
    {
        return $this->em->getRepository(Moneda::class)->findOneBy([
            'metodoPago' => $method,
            'habilitada' => true,
        ]);
    }

    /**
     * @return Moneda[]
     */
    private function findEnabledCurrenciesForMethod(string $method): array
    {
        return $this->em->getRepository(Moneda::class)->findBy([
            'metodoPago' => $method,
            'habilitada' => true,
        ], ['nombre' => 'ASC']);
    }

    private function resolveCurrencyIso(?Moneda $moneda): string
    {
        if (!$moneda instanceof Moneda) {
            return 'ARS';
        }

        $iso = $moneda->getCodigoIso() ?? $moneda->getSimbolo() ?? 'ARS';

        return strtoupper(substr($iso, 0, 3));
    }

    /**
     * @param Moneda[] $currencies
     * @return Moneda[]
     */
    private function uniqueCurrencies(array $currencies): array
    {
        $unique = [];
        foreach ($currencies as $currency) {
            if (!$currency instanceof Moneda) {
                continue;
            }
            $unique[$currency->getId()] = $currency;
        }

        return array_values($unique);
    }

    private function findCurrencyByIso(string $iso): ?Moneda
    {
        $iso = strtoupper(substr($iso, 0, 3));

        $repository = $this->em->getRepository(Moneda::class);
        $currency = $repository->findOneBy(['codigoIso' => $iso]);

        if (!$currency instanceof Moneda) {
            $currency = $repository->findOneBy(['simbolo' => $iso]);
        }

        return $currency;
    }

    private function buildUnavailableOption(string $type, ?Moneda $currency, string $reason): array
    {
        $iso = $currency instanceof Moneda ? $this->resolveCurrencyIso($currency) : null;

        return [
            'type' => $type,
            'label' => match ($type) {
                'mercadopago' => $iso ? sprintf('Mercado Pago (%s)', $iso) : 'Mercado Pago',
                'paypal' => $iso ? sprintf('PayPal (%s)', $iso) : 'PayPal',
                'cash' => $iso ? sprintf('Pago en efectivo (%s)', $iso) : 'Pago en efectivo',
                default => ucfirst($type),
            },
            'currency' => $iso,
            'displayCurrency' => $currency?->getNombre() ?? $iso,
            'total' => null,
            'route' => null,
            'params' => [],
            'available' => false,
            'reason' => $reason,
        ];
    }

    private function describeUnavailableBookingReason(?Moneda $currency, ?Precio $price, bool $credentialsValid, ?float $total): string
    {
        if (!$currency instanceof Moneda) {
            return 'Este medio de pago no tiene una moneda habilitada para esta reserva.';
        }

        if (!$price instanceof Precio) {
            return sprintf('Todavía no hay una tarifa cargada en %s para este medio de pago.', $this->resolveCurrencyIso($currency));
        }

        if ($total === null || $total <= 0) {
            return 'El monto configurado es inválido para procesar el pago.';
        }

        if (!$credentialsValid) {
            return 'Estamos actualizando la conexión con este medio de pago. Elegí otra opción por el momento.';
        }

        return 'Este medio de pago no está disponible temporalmente para esta reserva.';
    }

    private function describeUnavailableTransferReason(string $requestIso, ?Moneda $currency, bool $credentialsValid): string
    {
        if (!$currency instanceof Moneda) {
            return 'Este medio de pago no tiene una moneda habilitada para traslados.';
        }

        $methodIso = $this->resolveCurrencyIso($currency);
        if ($methodIso !== $requestIso) {
            return sprintf('Disponible únicamente para traslados cotizados en %s.', $methodIso);
        }

        if (!$credentialsValid) {
            return 'Estamos actualizando la conexión con este medio de pago.';
        }

        return 'Este medio de pago no está disponible temporalmente para este traslado.';
    }
}
