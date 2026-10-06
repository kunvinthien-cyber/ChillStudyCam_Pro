<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

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
        if (! $user) return response()->json(['message' => 'Unauthenticated.'], 401);

        return DB::transaction(function () use ($task, $user) {
            $lockedTask = Task::whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ((int) $lockedTask->user_id !== (int) $user->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            $lockedTask->is_completed = ! $lockedTask->is_completed;
            $earnedCoins = 0;
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($lockedTask->is_completed && ! $lockedTask->rewarded_at) {
                $lockedTask->rewarded_at = now();
                $lockedUser->coins += 5;
                $lockedUser->save();
                $earnedCoins = 5;
            }
            $lockedTask->save();

            return response()->json([
                'task' => $lockedTask,
                'earned_coins' => $earnedCoins,
                'total_coins' => $lockedUser->coins,
            ]);
        });
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
