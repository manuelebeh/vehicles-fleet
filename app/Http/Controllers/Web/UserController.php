<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AssignRoleRequest;
use App\Http\Requests\User\RemoveRoleRequest;
use App\Http\Requests\User\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use App\Traits\HandlesPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected UserService $userService
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $perPage = $this->getPerPage($request);
        $users = $this->userService->getAll($perPage);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): InertiaResponse
    {
        $roles = Role::all();

        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            
            $result = $this->userService->create($data);
            $user = $result['user'];
            $generatedPassword = $result['password'];
            
            if ($request->has('role_ids') && is_array($request->role_ids)) {
                $this->userService->syncRoles($user, $request->role_ids);
            }

            Log::info('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Utilisateur créé avec succès.')
                ->with('generated_password', $generatedPassword)
                ->with('user_email', $user->email);
        } catch (\Exception $e) {
            Log::error('Error creating user', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'created_by' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la création de l\'utilisateur.',
            ])->withInput();
        }
    }

    public function show(User $user): InertiaResponse
    {
        $user->load('roles', 'reservations');
        $roles = Role::all();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function edit(User $user): InertiaResponse
    {
        $user->load('roles');
        $roles = Role::all();

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        try {
            $data = $request->validated();
            
            unset($data['password']);

            $this->userService->update($user, $data);
            
            if ($request->has('role_ids') && is_array($request->role_ids)) {
                $this->userService->syncRoles($user, $request->role_ids);
            }

            $user->refresh();
            $user->load('roles');

            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'Utilisateur mis à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Error updating user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la mise à jour de l\'utilisateur.',
            ])->withInput();
        }
    }

    public function regeneratePassword(User $user): RedirectResponse
    {
        try {
            $result = $this->userService->regeneratePassword($user);
            $generatedPassword = $result['password'];

            Log::info('User password regenerated', [
                'user_id' => $user->id,
                'regenerated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'Mot de passe régénéré avec succès.')
                ->with('generated_password', $generatedPassword)
                ->with('user_email', $user->email);
        } catch (\Exception $e) {
            Log::error('Error regenerating password', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'regenerated_by' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la régénération du mot de passe.',
            ]);
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->hasRole('admin')) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Accès non autorisé.');
        }

        if ($user->id === $currentUser->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            $userId = $user->id;
            $this->userService->delete($user);

            Log::info('User deleted', [
                'user_id' => $userId,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Utilisateur supprimé avec succès.');
        } catch (\Exception $e) {
            Log::error('Error deleting user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Une erreur est survenue lors de la suppression de l\'utilisateur.');
        }
    }

    public function assignRole(AssignRoleRequest $request, User $user): RedirectResponse
    {
        try {
            $role = Role::findOrFail($request->role_id);
            $this->userService->assignRole($user, $role);

            Log::info('Role assigned to user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'assigned_by' => auth()->id(),
            ]);

            return back()->with('success', 'Rôle assigné avec succès.');
        } catch (\Exception $e) {
            Log::error('Error assigning role to user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'role_id' => $request->role_id,
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de l\'assignation du rôle.',
            ]);
        }
    }

    public function removeRole(RemoveRoleRequest $request, User $user): RedirectResponse
    {
        try {
            $role = Role::findOrFail($request->role_id);
            $this->userService->removeRole($user, $role);

            Log::info('Role removed from user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'removed_by' => auth()->id(),
            ]);

            return back()->with('success', 'Rôle retiré avec succès.');
        } catch (\Exception $e) {
            Log::error('Error removing role from user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'role_id' => $request->role_id,
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la suppression du rôle.',
            ]);
        }
    }
}
