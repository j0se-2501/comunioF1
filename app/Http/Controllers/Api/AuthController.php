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
    


    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'country'  => 'nullable|string|max:8',
            'profile_pic' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'country'  => $data['country'] ?? null,
            'profile_pic' => $data['profile_pic'] ?? null,
        ]);

         
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user,
        ]);
    }

    


    public function login(Request $request)
    {

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

         
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful',
            'user'    => Auth::user(),
        ]);
    }

    


    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

         
        $request->session()->invalidate();

         
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
