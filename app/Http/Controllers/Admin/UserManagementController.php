<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->value();
        $role = $request->string('role')->value();
        $isActive = $request->query('is_active');

        $users = User::query()
            ->with('roles')
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when($role, fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role)))
            ->when($isActive !== null && $isActive !== '', fn ($query) => $query->where('is_active', (bool) $isActive))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/UserManagement', [
            'users' => $users->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->values(),
            ]),
            'roles' => ['admin', 'technician', 'client'],
            'filters' => [
                'search' => $search,
                'role' => $role,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'email_verified_at' => now(),
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            $role = Role::findOrCreate($data['role'], config('auth.defaults.guard', 'web'));
            $user->syncRoles([$role]);

            if ($data['role'] === 'client') {
                $existingByEmail = Client::query()
                    ->whereNull('user_id')
                    ->where('email', $user->email)
                    ->first();

                if ($existingByEmail) {
                    $existingByEmail->update([
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                    ]);
                } else {
                    Client::query()->create([
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ]);
                }
            }
        });

        return back()->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        DB::transaction(function () use ($user, $payload, $data): void {
            $user->update($payload);
            $role = Role::findOrCreate($data['role'], config('auth.defaults.guard', 'web'));
            $user->syncRoles([$role]);

            if ($data['role'] === 'client') {
                Client::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ]
                );
            }
        });

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if (request()->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
