<?php

namespace App\Http\Controllers;

use App\Support\RoleRegistryAllowlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class InstructorRoleRegistryController extends Controller
{
    /**
     * Return the current merged role registry so clients can refresh role mapping.
     */
    public function index(): JsonResponse
    {
        $extras = RoleRegistryAllowlist::extras();
        $ta = array_values(array_unique(array_merge(
            (array) config('cognito.ta_allowlist', []),
            $extras['ta'],
        )));
        $professor = array_values(array_unique(array_merge(
            (array) config('cognito.professor_allowlist', []),
            $extras['professor'],
        )));

        return response()->json([
            'ta' => $ta,
            'professor' => $professor,
        ]);
    }

    /**
     * Persist TA/professor email lists from the SPA so Laravel role checks match the dashboard.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ta' => 'present|array',
            'ta.*' => 'email',
            'professor' => 'present|array',
            'professor.*' => 'email',
        ]);

        $normalize = static function (array $arr): array {
            return array_values(array_unique(array_map(
                static fn ($e) => strtolower(trim((string) $e)),
                $arr,
            )));
        };

        $ta = array_values(array_filter(
            $normalize($data['ta']),
            static fn (string $e): bool => str_ends_with($e, '@virginia.edu'),
        ));
        $professor = array_values(array_filter(
            $normalize($data['professor']),
            static fn (string $e): bool => str_ends_with($e, '@virginia.edu'),
        ));

        if (count($ta) > 500 || count($professor) > 500) {
            return response()->json(['message' => 'Too many entries.'], 422);
        }

        $path = storage_path('app/notifix_role_registry.json');
        File::put($path, json_encode([
            'ta' => $ta,
            'professor' => $professor,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return response()->json([
            'message' => 'Role registry saved.',
            'ta_count' => count($ta),
            'professor_count' => count($professor),
        ]);
    }
}
