<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    


    public function me()
    {
        return response()->json(Auth::user());
    }

    





    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'country'     => 'sometimes|string|max:8',  
            'profile_pic' => 'sometimes|string|nullable',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user,
        ]);
    }

    


    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 403);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }

    


    public function championships()
    {
        $user = Auth::user();

        $championships = $user->championships()
            ->with('season')
            ->get();

        return response()->json($championships);
    }

    






    public function show($userId)
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'country'     => $user->country,
            'profile_pic' => $user->profile_pic,
        ]);
    }
}
