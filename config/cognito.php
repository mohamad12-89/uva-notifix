<?php

/**
 * Built-in staff emails (must stay in sync with resources/js/composables/useAuthProfile.js
 * DEFAULT_TA_EMAILS / DEFAULT_PROFESSOR_EMAILS). Merged with env allowlists so API role
 * checks match the SPA for these accounts even when COGNITO_*_ALLOWLIST is unset.
 */
$defaultTaAllowlist = [
    'xfw9vp@virginia.edu',
    'uhu5nr@virginia.edu',
    'khg5bj@virginia.edu',
];

$defaultProfessorAllowlist = [
    'cdd9sb@virginia.edu',
    'amm8km@virginia.edu',
];

$taFromEnv = array_filter(array_map(
    static fn ($email) => strtolower(trim($email)),
    explode(',', (string) env('COGNITO_TA_ALLOWLIST', ''))
));

$professorFromEnv = array_filter(array_map(
    static fn ($email) => strtolower(trim($email)),
    explode(',', (string) env('COGNITO_PROFESSOR_ALLOWLIST', ''))
));

return [
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'user_pool_id' => env('COGNITO_USER_POOL_ID'),
    'client_id' => env('COGNITO_APP_CLIENT_ID'),
    'issuer' => env(
        'COGNITO_ISSUER',
        env('COGNITO_USER_POOL_ID') && env('AWS_DEFAULT_REGION')
            ? sprintf(
                'https://cognito-idp.%s.amazonaws.com/%s',
                env('AWS_DEFAULT_REGION'),
                env('COGNITO_USER_POOL_ID')
            )
            : null
    ),
    'jwks_url' => env(
        'COGNITO_JWKS_URL',
        env('COGNITO_USER_POOL_ID') && env('AWS_DEFAULT_REGION')
            ? sprintf(
                'https://cognito-idp.%s.amazonaws.com/%s/.well-known/jwks.json',
                env('AWS_DEFAULT_REGION'),
                env('COGNITO_USER_POOL_ID')
            )
            : null
    ),
    'professor_allowlist' => array_values(array_unique(array_merge(
        $defaultProfessorAllowlist,
        $professorFromEnv,
    ))),
    'ta_allowlist' => array_values(array_unique(array_merge(
        $defaultTaAllowlist,
        $taFromEnv,
    ))),
];
