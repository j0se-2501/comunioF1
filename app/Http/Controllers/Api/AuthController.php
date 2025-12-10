<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Registro de usuario
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'country'  => 'nullable|string|max:8',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'country'  => $data['country'] ?? null,
        ]);

        // Autologin tras registro
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user,
        ]);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {   
        return response()->json($request->all());

        $credentials = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $credentials['email'])->first();
        Log::info('login_attempt', [
            'email' => $credentials['email'],
            'found' => (bool) $user,
            'hash_check' => $user ? Hash::check($credentials['password'], $user->password) : null,
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Importante regenerar sesión
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful',
            'user'    => Auth::user(),
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        // Invalidar sesión actual
        $request->session()->invalidate();

        // Regenerar token CSRF
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
