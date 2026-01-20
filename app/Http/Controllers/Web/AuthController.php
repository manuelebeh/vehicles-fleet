<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->login(
            $request->email,
            $request->password
        );

        if (!$user) {
            Log::warning('Failed login attempt', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'Les identifiants fournis ne correspondent à aucun compte.',
            ])->onlyInput('email');
        }

        $user->load('roles');
        
        auth()->login($user);

        $request->session()->regenerate();

        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        if ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.index'));
        }

        return redirect()->intended(route('index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user) {
            Log::info('User logged out', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index');
    }
}
