<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([], 401);
        }

        return response()->json(
            Task::where('user_id', $user->id)->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'nullable|string|max:100',
        ]);

        $task = Task::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'subject' => $request->subject ?: 'ទូទៅ',
            'is_completed' => false,
        ]);

        return response()->json($task, 201);
    }

    public function toggle(Request $request, Task $task)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($task->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $task->is_completed = ! $task->is_completed;
        $task->save();

        $earnedCoins = 0;
        if ($task->is_completed) {
            $user->coins += 5;
            $user->save();
            $earnedCoins = 5;
        }

        return response()->json([
            'task' => $task,
            'earned_coins' => $earnedCoins,
            'total_coins' => $user->coins,
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($task->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'លុបកិច្ចការជោគជ័យ']);
    }
}
