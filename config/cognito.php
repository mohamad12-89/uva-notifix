<?php

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
    'professor_allowlist' => array_values(array_filter(array_map(
        static fn ($email) => strtolower(trim($email)),
        explode(',', (string) env('COGNITO_PROFESSOR_ALLOWLIST', ''))
    ))),
    'ta_allowlist' => array_values(array_filter(array_map(
        static fn ($email) => strtolower(trim($email)),
        explode(',', (string) env('COGNITO_TA_ALLOWLIST', ''))
    ))),
];
