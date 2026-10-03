<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json($user);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_hours' => 'required|numeric|min:1|max:24',
        ]);

        $user->name = $validated['name'];
        $user->target_hours = $validated['target_hours'];
        $user->save();

        return response()->json([
            'message' => 'បានកែប្រែព័ត៌មានជោគជ័យ! 🎉',
            'user' => $user,
        ]);
    }

    public function orders(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([], 401);
        }

        $orders = Order::where('user_id', $user->id)
            ->with('product')
            ->latest()
            ->take(10)
            ->get();

        return response()->json($orders);
    }
}
