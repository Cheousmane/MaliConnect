<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\GuidedTourProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuidedTourController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'tour' => $this->currentState($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['not_started', 'in_progress', 'completed', 'skipped'])],
            'current_step' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $role = $this->resolveRole($user);

        $progress = GuidedTourProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'role' => $role,
        ]);

        $status = $validated['status'];
        $progress->status = $status;
        $progress->current_step = $validated['current_step'] ?? $progress->current_step ?? 0;

        if ($status === 'in_progress' && $progress->started_at === null) {
            $progress->started_at = now();
        }

        if (in_array($status, ['completed', 'skipped'], true)) {
            $progress->completed_at = now();
        }

        if ($status === 'not_started') {
            $progress->started_at = null;
            $progress->completed_at = null;
            $progress->current_step = 0;
        }

        $progress->save();

        return response()->json([
            'tour' => $this->serialize($progress),
        ]);
    }

    public function restart(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);

        $progress = GuidedTourProgress::query()->updateOrCreate(
            ['user_id' => $user->id, 'role' => $role],
            [
                'status' => 'in_progress',
                'current_step' => 0,
                'started_at' => now(),
                'completed_at' => null,
            ],
        );

        return response()->json([
            'tour' => $this->serialize($progress),
        ]);
    }

    public static function currentState($user): ?array
    {
        if (! $user) {
            return null;
        }

        $role = static::resolveRole($user);
        $progress = GuidedTourProgress::query()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->first();

        if (! $progress) {
            return [
                'role' => $role,
                'status' => 'not_started',
                'current_step' => 0,
                'started_at' => null,
                'completed_at' => null,
            ];
        }

        return static::serialize($progress);
    }

    public static function resolveRole($user): string
    {
        $priority = [
            UserRole::SuperAdmin->value,
            UserRole::Admin->value,
            UserRole::CompanyManager->value,
            UserRole::Professional->value,
            UserRole::Customer->value,
        ];

        $roles = $user->getRoleNames()->all();

        foreach ($priority as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return UserRole::Customer->value;
    }

    private static function serialize(GuidedTourProgress $progress): array
    {
        return [
            'role' => $progress->role,
            'status' => $progress->status,
            'current_step' => $progress->current_step,
            'started_at' => $progress->started_at?->toIso8601String(),
            'completed_at' => $progress->completed_at?->toIso8601String(),
        ];
    }
}
