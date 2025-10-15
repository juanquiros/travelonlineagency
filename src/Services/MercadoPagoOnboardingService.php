<?php

namespace App\Services;

use App\Entity\CredencialesMercadoPago;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MercadoPagoOnboardingService
{
    private const AUTH_URL = 'https://auth.mercadopago.com/authorization';
    private const TOKEN_URL = 'https://api.mercadopago.com/oauth/token';
    private const USER_INFO_URL = 'https://api.mercadopago.com/users/me';
    private const BALANCE_URL = 'https://api.mercadopago.com/v1/account/balance';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function createAuthorizationUrl(CredencialesMercadoPago $credentials, string $redirectUri, string $state, array $scopes = []): string
    {
        if (!$credentials->getClientId()) {
            throw new \InvalidArgumentException('Falta el Client ID de Mercado Pago.');
        }

        $params = [
            'client_id' => $credentials->getClientId(),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'platform_id' => 'mp',
        ];

        if (!empty($scopes)) {
            $params['scopes'] = implode(' ', $scopes);
        }

        return self::AUTH_URL . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(CredencialesMercadoPago $credentials, string $authorizationCode, string $redirectUri): array
    {
        $response = $this->requestToken([
            'grant_type' => 'authorization_code',
            'client_id' => $credentials->getClientId(),
            'client_secret' => $credentials->getClientSecret(),
            'code' => $authorizationCode,
            'redirect_uri' => $redirectUri,
        ]);

        return $this->applyTokenPayload($credentials, $response);
    }

    public function refreshAccessToken(CredencialesMercadoPago $credentials): array
    {
        if (!$credentials->getRefreshToken()) {
            throw new \RuntimeException('La cuenta no tiene refresh token disponible.');
        }

        $response = $this->requestToken([
            'grant_type' => 'refresh_token',
            'client_id' => $credentials->getClientId(),
            'client_secret' => $credentials->getClientSecret(),
            'refresh_token' => $credentials->getRefreshToken(),
        ]);

        return $this->applyTokenPayload($credentials, $response);
    }

    public function ensureValidAccessToken(CredencialesMercadoPago $credentials): bool
    {
        if (!$credentials->getAccessToken()) {
            return false;
        }

        if ($credentials->tokenisvalid()) {
            return false;
        }

        $this->refreshAccessToken($credentials);

        return true;
    }

    public function syncAccountInformation(CredencialesMercadoPago $credentials): array
    {
        $this->ensureValidAccessToken($credentials);

        if (!$credentials->getAccessToken()) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::USER_INFO_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $credentials->getAccessToken(),
                ],
            ]);

            $data = $this->decodeResponse($response);
        } catch (TransportExceptionInterface | ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $exception) {
            throw new \RuntimeException('No se pudo obtener la información de la cuenta de Mercado Pago: ' . $exception->getMessage(), 0, $exception);
        }

        $credentials->setUserId(isset($data['id']) ? (int) $data['id'] : $credentials->getUserId());
        $credentials->setNickname($data['nickname'] ?? $credentials->getNickname());
        $credentials->setEmail($data['email'] ?? $credentials->getEmail());
        if (isset($data['public_key'])) {
            $credentials->setPublicKey($data['public_key']);
        }

        return $data;
    }

    public function fetchBalance(CredencialesMercadoPago $credentials): array
    {
        $this->ensureValidAccessToken($credentials);

        if (!$credentials->getAccessToken()) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::BALANCE_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $credentials->getAccessToken(),
                ],
            ]);

            return $this->decodeResponse($response);
        } catch (TransportExceptionInterface | ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $exception) {
            throw new \RuntimeException('No se pudo consultar el balance de Mercado Pago: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function applyTokenPayload(CredencialesMercadoPago $credentials, array $payload): array
    {
        if (isset($payload['access_token'])) {
            $credentials->setAccessToken($payload['access_token']);
        }

        if (isset($payload['refresh_token'])) {
            $credentials->setRefreshToken($payload['refresh_token']);
        }

        if (isset($payload['token_type'])) {
            $credentials->setTokenType($payload['token_type']);
        }

        if (isset($payload['scope'])) {
            $credentials->setScope($payload['scope']);
        }

        if (isset($payload['expires_in'])) {
            $credentials->setExpiresIn((int) $payload['expires_in']);
        }

        if (isset($payload['user_id'])) {
            $credentials->setUserId((int) $payload['user_id']);
        }

        if (isset($payload['public_key'])) {
            $credentials->setPublicKey($payload['public_key']);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function requestToken(array $payload): array
    {
        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $payload,
            ]);

            $data = $this->decodeResponse($response);
        } catch (TransportExceptionInterface | ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $exception) {
            throw new \RuntimeException('No se pudo obtener el token de Mercado Pago: ' . $exception->getMessage(), 0, $exception);
        }

        if (isset($data['error'])) {
            throw new \RuntimeException('Mercado Pago rechazó la solicitud: ' . ($data['error_description'] ?? $data['error']));
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(ResponseInterface $response): array
    {
        $content = $response->getContent(false);
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Respuesta inesperada de Mercado Pago.');
        }

        return $decoded;
    }
}
