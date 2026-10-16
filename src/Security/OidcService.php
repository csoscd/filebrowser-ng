<?php

declare(strict_types=1);

namespace FilebrowserNg\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

class OidcService
{
    private ?array $discovery = null;
    private ?array $jwkKeys   = null;

    public function __construct(
        private readonly string          $clientId,
        private readonly string          $clientSecret,
        private readonly string          $discoveryUrl,
        private readonly string          $redirectUri,
        private readonly string          $smbKeyClaim,
        private readonly string          $usernameClaim,
        private readonly LoggerInterface $logger,
        private readonly bool            $verifySsl = true,
    ) {}

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->discoveryUrl !== '';
    }

    /** Generiert die Authorization-URL; state und nonce müssen vorher in der Session gespeichert werden. */
    public function buildAuthorizationUrl(string $state, string $nonce): string
    {
        $disco  = $this->getDiscovery();
        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => 'openid profile',
            'state'         => $state,
            'nonce'         => $nonce,
        ]);
        return $disco['authorization_endpoint'] . '?' . $params;
    }

    /**
     * Tauscht den Authorization Code gegen Tokens aus.
     * @return array{id_token: string, access_token: string, ...}
     */
    public function exchangeCode(string $code): array
    {
        $disco  = $this->getDiscovery();
        $client = $this->makeHttpClient();

        try {
            $response = $client->post($disco['token_endpoint'], [
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'redirect_uri'  => $this->redirectUri,
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);
        } catch (GuzzleException $e) {
            $this->logger->error('OIDC token exchange failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('Token exchange failed: ' . $e->getMessage(), 0, $e);
        }

        $data = json_decode((string) $response->getBody(), true);
        if (!isset($data['id_token'])) {
            throw new RuntimeException('Token response missing id_token');
        }
        return $data;
    }

    /**
     * Validiert das ID Token (Signatur, nonce, audience) und gibt die Claims zurück.
     * @throws RuntimeException bei ungültigem Token
     */
    public function validateIdToken(string $idToken, string $expectedNonce): stdClass
    {
        $keys    = $this->getJwkKeys();
        $keySet  = JWK::parseKeySet(['keys' => $keys]);

        $claims = JWT::decode($idToken, $keySet);

        if (!isset($claims->nonce) || $claims->nonce !== $expectedNonce) {
            throw new RuntimeException('OIDC nonce mismatch');
        }

        $aud = is_array($claims->aud) ? $claims->aud : [$claims->aud];
        if (!in_array($this->clientId, $aud, true)) {
            throw new RuntimeException('OIDC invalid audience');
        }

        return $claims;
    }

    /** Extrahiert den Benutzernamen aus den Claims (preferred_username → sub). */
    public function extractUsername(stdClass $claims): string
    {
        if ($this->usernameClaim !== '' && isset($claims->{$this->usernameClaim})) {
            return (string) $claims->{$this->usernameClaim};
        }
        return (string) ($claims->preferred_username ?? $claims->sub ?? '');
    }

    /** Extrahiert den smb_key Claim (null wenn nicht vorhanden). */
    public function extractSmbKey(stdClass $claims): ?string
    {
        if ($this->smbKeyClaim === '') {
            return null;
        }
        $value = $claims->{$this->smbKeyClaim} ?? null;
        return $value !== null ? (string) $value : null;
    }

    /** Erzeugt einen sicheren Zufalls-String (state / nonce). */
    public static function generateRandomString(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /** Liest und cachet die OpenID Configuration. */
    private function getDiscovery(): array
    {
        if ($this->discovery !== null) {
            return $this->discovery;
        }

        $url = str_ends_with($this->discoveryUrl, 'openid-configuration')
            ? $this->discoveryUrl
            : rtrim($this->discoveryUrl, '/') . '/.well-known/openid-configuration';

        $client = $this->makeHttpClient();
        try {
            $response = $client->get($url);
        } catch (GuzzleException $e) {
            $this->logger->error('OIDC discovery failed', ['url' => $url, 'error' => $e->getMessage()]);
            throw new RuntimeException('OIDC discovery failed: ' . $e->getMessage(), 0, $e);
        }

        $data = json_decode((string) $response->getBody(), true);
        if (!isset($data['authorization_endpoint'], $data['token_endpoint'], $data['jwks_uri'])) {
            throw new RuntimeException('OIDC discovery response missing required fields');
        }

        $this->discovery = $data;
        return $this->discovery;
    }

    /** Liest und cachet die JWKS. */
    private function getJwkKeys(): array
    {
        if ($this->jwkKeys !== null) {
            return $this->jwkKeys;
        }

        $disco  = $this->getDiscovery();
        $client = $this->makeHttpClient();
        try {
            $response = $client->get($disco['jwks_uri']);
        } catch (GuzzleException $e) {
            $this->logger->error('OIDC JWKS fetch failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('OIDC JWKS fetch failed: ' . $e->getMessage(), 0, $e);
        }

        $data = json_decode((string) $response->getBody(), true);
        $this->jwkKeys = $data['keys'] ?? [];
        return $this->jwkKeys;
    }

    private function makeHttpClient(): Client
    {
        return new Client([
            'timeout'         => 10.0,
            'connect_timeout' => 5.0,
            'verify'          => $this->verifySsl,
        ]);
    }
}
