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
            $precio = $this->findBookingPrice($booking, 2); // ARS
            $credencial = $this->resolveMercadoPagoCredentials($booking->getBookingPartner(), $plataforma);
            if ($precio && $credencial && $this->hasValidMercadoPagoCredentials($credencial)) {
                $total = (float) $precio->getValor() * $cantidad;
                if ($total > 0) {
                    $options[] = [
                        'type' => 'mercadopago',
                        'label' => 'Mercado Pago',
                        'currency' => 'ARS',
                        'displayCurrency' => 'ARS',
                        'total' => $total,
                        'route' => 'mercadopago_pay_booking',
                        'params' => ['id' => $reserva->getId()],
                    ];
                }
            }
        }

        if ($plataforma->isEnablePayPalPayments()) {
            $precioUsd = $this->findBookingPrice($booking, 1); // USD
            $credenciales = $plataforma->getCredencialesPayPal();
            if ($precioUsd && $this->hasValidPayPalCredentials($credenciales)) {
                $totalUsd = (float) $precioUsd->getValor() * $cantidad;
                if ($totalUsd > 0) {
                    $options[] = [
                        'type' => 'paypal',
                        'label' => 'PayPal',
                        'currency' => 'USD',
                        'displayCurrency' => 'USD',
                        'total' => $totalUsd,
                        'route' => 'paypal_pay_booking',
                        'params' => ['id' => $reserva->getId()],
                    ];
                }
            }
        }

        if ($plataforma->isEnableCashPayments()) {
            $moneda = $plataforma->getMonedaDef();
            $precioLocal = $moneda ? $this->findBookingPrice($booking, $moneda->getId()) : null;
            $precioFallback = $precioLocal ?? $this->findBookingPrice($booking, 2) ?? $this->findBookingPrice($booking, 1);
            if ($precioFallback) {
                $totalCash = (float) $precioFallback->getValor() * $cantidad;
                if ($totalCash > 0) {
                    $monedaEntidad = $precioFallback->getMoneda();
                    $currencyCode = $monedaEntidad?->getSimbolo() ?? 'ARS';
                    $monedaDisplay = $monedaEntidad?->getNombre() ?? $currencyCode;
                    $options[] = [
                        'type' => 'cash',
                        'label' => 'Pago en efectivo',
                        'currency' => strtoupper(substr($currencyCode, 0, 3)),
                        'displayCurrency' => $monedaDisplay,
                        'total' => $totalCash,
                        'route' => 'cash_pay_booking',
                        'params' => ['id' => $reserva->getId()],
                        'instructions' => $plataforma->getCashPaymentInstructions(),
                    ];
                }
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

        if ($plataforma->isEnableMercadoPagoPayments()) {
            $credencial = $plataforma->getCredencialesMercadoPago();
            if ($credencial && $this->hasValidMercadoPagoCredentials($credencial)) {
                $options[] = [
                    'type' => 'mercadopago',
                    'label' => 'Mercado Pago',
                    'currency' => $solicitud->getMoneda(),
                    'displayCurrency' => $solicitud->getMoneda(),
                    'total' => $total,
                    'route' => 'mercadopago_pay_transfer',
                    'params' => ['id' => $solicitud->getId()],
                ];
            }
        }

        if ($plataforma->isEnablePayPalPayments()) {
            $credenciales = $plataforma->getCredencialesPayPal();
            if ($this->hasValidPayPalCredentials($credenciales)) {
                $options[] = [
                    'type' => 'paypal',
                    'label' => 'PayPal',
                    'currency' => 'USD',
                    'displayCurrency' => 'USD',
                    'total' => $total,
                    'route' => 'paypal_pay_transfer',
                    'params' => ['id' => $solicitud->getId()],
                ];
            }
        }

        if ($plataforma->isEnableCashPayments()) {
            $options[] = [
                'type' => 'cash',
                'label' => 'Pago en efectivo',
                'currency' => strtoupper(substr($solicitud->getMoneda(), 0, 3)),
                'displayCurrency' => $solicitud->getMoneda(),
                'total' => $total,
                'route' => 'cash_pay_transfer',
                'params' => ['id' => $solicitud->getId()],
                'instructions' => $plataforma->getCashPaymentInstructions(),
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
}
