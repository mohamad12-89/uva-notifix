<?php

namespace App\Support;

use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use UnexpectedValueException;

class CognitoJwtVerifier
{
    /**
     * @return array<string,mixed>
     */
    public function verify(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new UnexpectedValueException('Invalid JWT format.');
        }

        $headerJson = $this->decodeSegment($parts[0]);
        $header = json_decode($headerJson, true);
        if (! is_array($header) || empty($header['kid'])) {
            throw new UnexpectedValueException('JWT header is missing kid.');
        }

        $key = $this->resolveKey((string) $header['kid']);
        $decoded = JWT::decode($jwt, $key);
        $claims = (array) $decoded;

        $issuer = (string) config('cognito.issuer');
        if (! $issuer || ($claims['iss'] ?? null) !== $issuer) {
            throw new UnexpectedValueException('JWT issuer mismatch.');
        }

        $clientId = (string) config('cognito.client_id');
        $audience = $claims['aud'] ?? null;
        $client = $claims['client_id'] ?? null;
        if ($audience !== $clientId && $client !== $clientId) {
            throw new UnexpectedValueException('JWT audience/client mismatch.');
        }

        if (($claims['token_use'] ?? null) !== 'id' && ($claims['token_use'] ?? null) !== 'access') {
            throw new UnexpectedValueException('Unsupported token_use.');
        }

        return $claims;
    }

    private function resolveKey(string $kid): Key
    {
        $jwks = Cache::remember('cognito:jwks', now()->addHours(6), function () {
            $url = (string) config('cognito.jwks_url');
            if (! $url) {
                throw new RuntimeException('COGNITO_JWKS_URL is not configured.');
            }

            $response = Http::timeout(5)->get($url);
            if (! $response->ok()) {
                throw new RuntimeException('Unable to fetch Cognito JWKS.');
            }

            $json = $response->json();
            if (! is_array($json) || ! isset($json['keys'])) {
                throw new RuntimeException('Invalid Cognito JWKS response.');
            }

            return $json;
        });

        $keys = JWK::parseKeySet($jwks);
        if (! isset($keys[$kid])) {
            Cache::forget('cognito:jwks');
            throw new UnexpectedValueException('JWT kid not found in JWKS.');
        }

        return $keys[$kid];
    }

    private function decodeSegment(string $segment): string
    {
        $remainder = strlen($segment) % 4;
        if ($remainder !== 0) {
            $segment .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($segment, '-_', '+/'), true);
        if ($decoded === false) {
            throw new UnexpectedValueException('Invalid JWT segment encoding.');
        }

        return $decoded;
    }
}
