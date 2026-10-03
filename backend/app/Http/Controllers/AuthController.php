<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ១. ចុះឈ្មោះសិស្សថ្មី (Register)
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // បង្កើត User ថ្មី និងផ្តល់កាដូស្វាគមន៍ ១០០ PTS ដំបូង!
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'coins' => 100, // កាដូស្វាគមន៍
            'streak_days' => 1,
            'rank_title' => 'Angkor Explorer',
            'studied_hours' => 0.0,
            'target_hours' => 4.0,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'ចុះឈ្មោះបានជោគជ័យ! សូមស្វាគមន៍មកកាន់ ChillStudy 🎉',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // ២. ចូលគណនី (Login)
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['អ៊ីមែល ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវឡើយ!'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'ចូលគណនីបានជោគជ័យ!',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // ៣. ចាកចេញពីគណនី (Logout)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ចាកចេញពីគណនីបានជោគជ័យ']);
    }

    // ៤. ទាញយកព័ត៌មាន User បច្ចុប្បន្ន
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
