<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\RoomParticipant;
use App\Models\StudySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudySessionController extends Controller
{
    public function start(Request $request)
    {
        $validated = $request->validate([
            'session_key' => ['required', 'uuid'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
        ]);
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated.'], 401);

        $isPresent = RoomParticipant::where('room_id', $validated['room_id'])
            ->where('user_id', $user->id)
            ->where('last_seen_at', '>=', now()->subSeconds(30))
            ->exists();
        if (! $isPresent) return response()->json(['message' => 'Join the room before starting a study session.'], 403);

        $session = StudySession::firstOrCreate(
            ['user_id' => $user->id, 'session_key' => $validated['session_key']],
            ['room_id' => $validated['room_id'], 'started_at' => now(), 'duration_minutes' => 25]
        );
        if ($session->completed_at) return response()->json(['message' => 'This study session is already completed.'], 409);

        return response()->json(['session_key' => $session->session_key, 'started_at' => $session->started_at]);
    }

    public function complete(Request $request)
    {
        $validated = $request->validate(['session_key' => ['required', 'uuid']]);
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated.'], 401);

        $result = DB::transaction(function () use ($validated, $user) {
            $session = StudySession::where('user_id', $user->id)
                ->where('session_key', $validated['session_key'])
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($session->completed_at) {
                return ['earned_coins' => 0, 'user' => $lockedUser, 'studied_hours' => $lockedUser->studied_hours];
            }
            if ($session->started_at->addMinutes($session->duration_minutes)->isFuture()) {
                return ['error' => 'A 25-minute study session must finish before claiming its reward.', 'status' => 422];
            }

            $addedHours = round($session->duration_minutes / 60, 2);
            $session->update(['completed_at' => now(), 'coins_awarded' => 25]);
            $lockedUser->coins += 25;
            $lockedUser->studied_hours = round($lockedUser->studied_hours + $addedHours, 2);
            $lockedUser->save();

            $campus = Campus::query()->orderBy('id')->lockForUpdate()->first();
            if ($campus) {
                $campus->total_hours = round($campus->total_hours + $addedHours, 2);
                $campus->save();
            }

            return ['earned_coins' => 25, 'user' => $lockedUser, 'studied_hours' => $lockedUser->studied_hours];
        });

        if (isset($result['error'])) return response()->json(['message' => $result['error']], $result['status']);

        return response()->json([
            'status' => 'success',
            'message' => $result['earned_coins'] > 0 ? 'Study session complete. Reward added.' : 'This session reward was already claimed.',
            'reward' => [
                'earned_coins' => $result['earned_coins'],
                'total_coins' => $result['user']->coins,
                'studied_hours' => $result['studied_hours'],
            ],
        ]);
    }

    public function getLeaderboard()
    {
        return response()->json(Campus::orderBy('total_hours', 'desc')->get());
    }
}