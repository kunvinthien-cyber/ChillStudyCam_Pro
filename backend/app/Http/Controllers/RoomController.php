<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomInvite;
use App\Models\RoomMessage;
use App\Models\RoomParticipant;
use App\Models\RoomReaction;
use App\Models\User;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        return response()->json(Room::latest()->get());
    }

    public function show(Room $room)
    {
        return response()->json($room);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle_khmer' => 'required|string|max:255',
            'category_tag' => 'required|string',
            'grade_level' => 'required|string',
            'is_private' => 'nullable|boolean',
            'passcode' => ['nullable', 'string', 'min:4', 'max:6'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isPrivate = (bool) $request->boolean('is_private');
        if ($isPrivate && blank($request->passcode)) {
            return response()->json(['message' => 'Private rooms require a passcode.'], 422);
        }

        $room = Room::create([
            'creator_id' => $user->id,
            'title' => $validated['title'],
            'subtitle_khmer' => $validated['subtitle_khmer'],
            'description' => $request->description ?: 'បន្ទប់សិក្សាបង្កើតដោយ ' . $user->name,
            'category_tag' => $validated['category_tag'],
            'badge_tag' => $isPrivate ? '🔒 Private' : 'Community',
            'ambient_title' => 'Lofi Beats & Focus',
            'thumbnail' => $request->thumbnail ?: 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?q=80&w=1000&auto=format&fit=crop',
            'active_students' => 1,
            'grade_level' => $validated['grade_level'] ?: 'all',
            'subject_focus' => $request->subject_focus ?: 'ទូទៅ',
            'is_private' => $isPrivate,
            'passcode' => $isPrivate ? (string) $request->passcode : null,
        ]);

        return response()->json($room, 201);
    }

    public function verifyPasscode(Request $request, $roomId)
    {
        $room = Room::findOrFail($roomId);

        if (! $room->is_private) {
            return response()->json(['valid' => true]);
        }

        return response()->json([
            'valid' => $room->passcode !== null && $room->passcode === (string) $request->input('passcode'),
            'message' => $room->passcode !== null && $room->passcode === (string) $request->input('passcode')
                ? 'PIN accepted.'
                : 'លេខកូដសម្ងាត់ PIN មិនត្រឹមត្រូវឡើយ!',
        ], $room->passcode !== null && $room->passcode === (string) $request->input('passcode') ? 200 : 403);
    }

    public function getMessages($roomId)
    {
        $messages = RoomMessage::where('room_id', $roomId)
            ->latest()
            ->take(25)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $roomId)
    {
        $request->validate(['message' => 'required|string|max:500']);

        $user = $request->user();
        $userName = $user?->name ?? 'សិស្ស Chill';

        $message = RoomMessage::create([
            'room_id' => $roomId,
            'user_id' => $user?->id,
            'user_name' => $userName,
            'message' => $request->message,
        ]);

        return response()->json($message, 201);
    }

    public function sendReaction(Request $request, $roomId)
    {
        $request->validate(['emoji' => 'required|string|max:32']);

        $user = $request->user();

        $reaction = RoomReaction::create([
            'room_id' => $roomId,
            'user_name' => $user?->name ?? 'សិស្ស Chill',
            'emoji' => $request->emoji,
        ]);

        return response()->json($reaction, 201);
    }

    public function getRecentReactions($roomId)
    {
        $reactions = RoomReaction::where('room_id', $roomId)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->latest()
            ->get();

        return response()->json($reactions);
    }

    public function getParticipants($roomId)
    {
        RoomParticipant::where('room_id', $roomId)
            ->where('last_seen_at', '<', now()->subSeconds(30))
            ->delete();

        return response()->json(RoomParticipant::where('room_id', $roomId)->get());
    }

    public function joinVoice(Request $request, $roomId)
    {
        $request->validate(['peer_id' => 'required|string']);

        $user = $request->user();
        $participant = RoomParticipant::updateOrCreate(
            ['room_id' => $roomId, 'peer_id' => $request->peer_id],
            [
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'សិស្ស Chill',
                'is_in_voice' => true,
                'is_muted' => false,
                'last_seen_at' => now(),
            ]
        );

        return response()->json($participant);
    }

    public function leaveVoice(Request $request, $roomId)
    {
        if ($request->filled('peer_id')) {
            RoomParticipant::where('room_id', $roomId)
                ->where('peer_id', $request->peer_id)
                ->delete();
        }

        return response()->json(['message' => 'Left voice']);
    }

    public function updateGoal(Request $request, $roomId)
    {
        $request->validate(['peer_id' => 'required|string', 'goal' => 'required|string']);

        RoomParticipant::where('room_id', $roomId)
            ->where('peer_id', $request->peer_id)
            ->update(['study_goal' => $request->goal]);

        return response()->json(['message' => 'Goal updated']);
    }

    public function searchUsers(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        return response()->json(
            User::query()
                ->when($query !== '', fn ($queryBuilder) => $queryBuilder->where('name', 'like', "%{$query}%"))
                ->take(5)
                ->get(['id', 'name', 'email', 'rank_title'])
        );
    }

    public function sendInvite(Request $request, $roomId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'invitee_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        RoomInvite::create([
            'room_id' => $roomId,
            'inviter_id' => $user->id,
            'invitee_id' => $request->invitee_id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'បានផ្ញើលិខិតអញ្ជើញជោគជ័យ!']);
    }

    public function getMyInvites(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([]);
        }

        return response()->json(
            RoomInvite::where('invitee_id', $user->id)
                ->where('status', 'pending')
                ->with(['room', 'inviter:id,name'])
                ->latest()
                ->get()
        );
    }

    public function respondInvite(Request $request, $inviteId)
    {
        $invite = RoomInvite::findOrFail($inviteId);
        $invite->update(['status' => $request->boolean('accept') || $request->input('action') === 'accept' ? 'accepted' : 'rejected']);

        return response()->json(['status' => $invite->status, 'room_id' => $invite->room_id]);
    }

    public function requestJoin(Request $request, $roomId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        RoomParticipant::updateOrCreate(
            ['room_id' => $roomId, 'user_id' => $user->id],
            ['user_name' => $user->name, 'study_goal' => 'pending_approval', 'is_in_voice' => false, 'last_seen_at' => now()]
        );

        return response()->json(['message' => 'សំណើត្រូវបានផ្ញើ!']);
    }

    public function checkJoinStatus(Request $request, $roomId)
    {
        $user = $request->user();
        $participant = RoomParticipant::where('room_id', $roomId)
            ->where('user_id', $user?->id ?? 0)
            ->first();

        return response()->json(['approved' => (bool) ($participant && $participant->study_goal !== 'pending_approval')]);
    }

    public function approveMember(Request $request, $roomId, $participantId)
    {
        $participant = RoomParticipant::where('room_id', $roomId)->find($participantId);

        if (! $participant) {
            return response()->json(['message' => 'រកមិនឃើញសំណើ'], 404);
        }

        if ($request->input('action') === 'approve') {
            $participant->update(['study_goal' => 'បានចូលរៀនផ្លូវការ']);
            return response()->json(['message' => 'បានអនុញ្ញាត!']);
        }

        $participant->delete();

        return response()->json(['message' => 'បានបដិសេធ!']);
    }
}
