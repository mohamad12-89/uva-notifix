<?php

namespace App\Support;

/**
 * Extra TA/professor emails persisted when a professor syncs from the Instructor Dashboard.
 *
 * @phpstan-type RegistryShape array{ta: array<int, string>, professor: array<int, string>}
 */
final class RoleRegistryAllowlist
{
    /**
     * @return RegistryShape
     */
    public static function extras(): array
    {
        $path = storage_path('app/notifix_role_registry.json');
        if (! is_file($path)) {
            return ['ta' => [], 'professor' => []];
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return ['ta' => [], 'professor' => []];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return ['ta' => [], 'professor' => []];
        }

        if (! is_array($decoded)) {
            return ['ta' => [], 'professor' => []];
        }

        $ta = $decoded['ta'] ?? [];
        $prof = $decoded['professor'] ?? [];
        $norm = static fn (mixed $e): string => strtolower(trim((string) $e));

        return [
            'ta' => array_values(array_unique(array_filter(
                is_array($ta) ? array_map($norm, $ta) : [],
                static fn (string $e): bool => $e !== '' && str_ends_with($e, '@virginia.edu'),
            ))),
            'professor' => array_values(array_unique(array_filter(
                is_array($prof) ? array_map($norm, $prof) : [],
                static fn (string $e): bool => $e !== '' && str_ends_with($e, '@virginia.edu'),
            ))),
        ];
    }
}
