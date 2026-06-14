<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $users = User::query()
            ->withCount('documents')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when(
                array_key_exists('is_active', $validated),
                fn ($query) => $query->where('is_active', $validated['is_active']),
            )
            ->latest()
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json([
            'data' => $users,
        ]);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->user()->is($user) && ! $validated['is_active']) {
            return response()->json([
                'message' => 'Admin tidak dapat menonaktifkan akunnya sendiri.',
            ], 422);
        }

        $user->update([
            'is_active' => $validated['is_active'],
        ]);

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $user->is_active ? 'User berhasil diaktifkan.' : 'User berhasil dinonaktifkan.',
            'data' => $user,
        ]);
    }
}
