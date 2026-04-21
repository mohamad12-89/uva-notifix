<?php

namespace App\Http\Middleware;

use App\Support\CognitoJwtVerifier;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateCognito
{
    public function __construct(private readonly CognitoJwtVerifier $verifier)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization');
        if (! $auth || ! str_starts_with($auth, 'Bearer ')) {
            return new JsonResponse(['message' => 'Missing bearer token.'], 401);
        }

        $token = trim(substr($auth, 7));
        if ($token === '') {
            return new JsonResponse(['message' => 'Missing bearer token.'], 401);
        }

        try {
            $claims = $this->verifier->verify($token);
        } catch (Throwable $e) {
            return new JsonResponse(['message' => 'Invalid token.', 'error' => $e->getMessage()], 401);
        }

        $email = strtolower((string) ($claims['email'] ?? ''));
        $groups = $claims['cognito:groups'] ?? [];
        if (! is_array($groups)) {
            $groups = [];
        }

        $role = $this->resolveRole($email, $groups);
        $request->attributes->set('auth_claims', $claims);
        $request->attributes->set('auth_email', $email);
        $request->attributes->set('auth_sub', (string) ($claims['sub'] ?? ''));
        $request->attributes->set('auth_role', $role);

        return $next($request);
    }

    /**
     * @param array<int,string> $groups
     */
    private function resolveRole(string $email, array $groups): string
    {
        $normalized = array_map(static fn ($g) => strtolower(trim((string) $g)), $groups);
        if (in_array('professor', $normalized, true)) {
            return 'professor';
        }
        if (in_array('ta', $normalized, true)) {
            return 'ta';
        }
        if (in_array('student', $normalized, true)) {
            return 'student';
        }

        if ($email && in_array($email, (array) config('cognito.professor_allowlist', []), true)) {
            return 'professor';
        }
        if ($email && in_array($email, (array) config('cognito.ta_allowlist', []), true)) {
            return 'ta';
        }

        return 'student';
    }
}
