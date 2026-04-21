<?php

namespace App\Http\Middleware;

use App\Support\CognitoJwtVerifier;
use App\Support\RoleRegistryAllowlist;
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

        $email = $this->resolveEmailFromClaims($claims);
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
     * @param array<string, mixed> $claims
     */
    private function resolveEmailFromClaims(array $claims): string
    {
        $raw = trim((string) ($claims['email'] ?? ''));
        if ($raw !== '' && str_contains($raw, '@')) {
            return strtolower($raw);
        }

        // Access tokens often omit `email`; username may be the sign-in identifier.
        foreach (['username', 'cognito:username'] as $key) {
            $candidate = strtolower(trim((string) ($claims[$key] ?? '')));
            if ($candidate !== '' && str_contains($candidate, '@')) {
                return $candidate;
            }
        }

        return strtolower($raw);
    }

    /**
     * @param array<int,string> $groups
     */
    private function resolveRole(string $email, array $groups): string
    {
        $registry = RoleRegistryAllowlist::extras();
        $professorAllowlist = array_values(array_unique(array_merge(
            (array) config('cognito.professor_allowlist', []),
            $registry['professor'],
        )));
        $taAllowlist = array_values(array_unique(array_merge(
            (array) config('cognito.ta_allowlist', []),
            $registry['ta'],
        )));

        $normalized = array_map(static fn ($g) => strtolower(trim((string) $g)), $groups);
        if (in_array('professor', $normalized, true)) {
            return 'professor';
        }
        if (in_array('ta', $normalized, true)) {
            return 'ta';
        }

        // Allowlists must run before the generic "student" group, or every pool
        // member in group "student" is locked out of TA/professor API routes.
        if ($email && in_array($email, $professorAllowlist, true)) {
            return 'professor';
        }
        if ($email && in_array($email, $taAllowlist, true)) {
            return 'ta';
        }

        if (in_array('student', $normalized, true)) {
            return 'student';
        }

        return 'student';
    }
}
