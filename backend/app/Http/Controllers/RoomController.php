<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomInvite;
use App\Models\RoomMessage;
use App\Models\RoomParticipant;
use App\Models\RoomReaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RoomController extends Controller
{
    private function matchesRoomPasscode(string $input, string $stored): bool
    {
        if ($stored === '') return false;
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
            return Hash::check($input, $stored);
        }

        return hash_equals($stored, $input);
    }

    private function roomAccessError(Request $request, $roomId)
    {
        $room = Room::findOrFail($roomId);
        if (! $room->is_private || ($room->access_mode ?? 'pin') === 'public') return null;

        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated.'], 401);
        if ((int) $room->creator_id === (int) $user->id || (int) $user->id === 1) return null;

        $hasApprovedParticipant = RoomParticipant::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('study_goal', '!=', 'pending_approval')
            ->exists();
        $hasAcceptedInvite = RoomInvite::where('room_id', $room->id)
            ->where('invitee_id', $user->id)
            ->where('status', 'accepted')
            ->exists();

        if ($hasApprovedParticipant || $hasAcceptedInvite) return null;

        return response()->json(['message' => 'PIN verification or room approval is required.'], 403);
    }

    public function index()
    {
        return response()->json(Room::latest()->get());
    }

    public function show(Request $request, Room $room)
    {
        if ($denied = $this->roomAccessError($request, $room->id)) return $denied;
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
            'access_mode' => ['nullable', 'in:public,pin,approval'],
            'passcode' => ['nullable', 'string', 'min:4', 'max:6'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessMode = $validated['access_mode'] ?? ($request->boolean('is_private') ? 'pin' : 'public');
        $isPrivate = $accessMode !== 'public';
        if ($accessMode === 'pin' && blank($request->passcode)) {
            return response()->json(['message' => 'PIN rooms require a passcode.'], 422);
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
            'access_mode' => $accessMode,
            'passcode' => $accessMode === 'pin' ? Hash::make((string) $request->passcode) : null,
        ]);

        return response()->json($room, 201);
    }

    public function verifyPasscode(Request $request, $roomId)
    {
        $request->validate(['passcode' => ['required', 'string', 'max:6']]);
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated.'], 401);

        $room = Room::findOrFail($roomId);
        if (! $room->is_private) return response()->json(['valid' => true]);
        if (($room->access_mode ?? 'pin') !== 'pin') {
            return response()->json(['valid' => false, 'message' => 'This room uses admin approval.'], 422);
        }

        $passcode = (string) $request->input('passcode');
        $storedPasscode = (string) $room->passcode;
        if (! $this->matchesRoomPasscode($passcode, $storedPasscode)) {
            return response()->json(['valid' => false, 'message' => 'Invalid room PIN.'], 403);
        }

        if (! str_starts_with($storedPasscode, '$2y$') && ! str_starts_with($storedPasscode, '$argon2')) {
            $room->passcode = Hash::make($passcode);
            $room->save();
        }

        RoomParticipant::updateOrCreate(
            ['room_id' => $room->id, 'user_id' => $user->id],
            ['user_name' => $user->name, 'last_seen_at' => now(), 'study_goal' => null]
        );

        return response()->json(['valid' => true]);
    }

    public function getMessages(Request $request, $roomId)
    {
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
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
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
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
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
        $request->validate(['emoji' => 'required|string|max:32']);

        $user = $request->user();

        $reaction = RoomReaction::create([
            'room_id' => $roomId,
            'user_name' => $user?->name ?? 'សិស្ស Chill',
            'emoji' => $request->emoji,
        ]);

        return response()->json($reaction, 201);
    }

    public function getRecentReactions(Request $request, $roomId)
    {
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
        $reactions = RoomReaction::where('room_id', $roomId)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->latest()
            ->get();

        return response()->json($reactions);
    }

    public function getParticipants(Request $request, $roomId)
    {
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
        RoomParticipant::where('room_id', $roomId)
            ->where('last_seen_at', '<', now()->subSeconds(30))
            ->delete();

        $participants = RoomParticipant::where('room_id', $roomId)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn ($participant) => $participant->user_id
                ? 'user:' . $participant->user_id
                : 'participant:' . $participant->id)
            ->values();

        return response()->json($participants);
    }

    public function joinVoice(Request $request, $roomId)
    {
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
        $request->validate(['peer_id' => 'required|string']);

        $user = $request->user();
        $participant = RoomParticipant::where('room_id', $roomId)
            ->where('user_id', $user?->id)
            ->first();

        if (! $participant) {
            $participant = RoomParticipant::firstOrNew(['room_id' => $roomId, 'peer_id' => $request->peer_id]);
        }

        $participant->fill([
            'user_id' => $user?->id,
            'peer_id' => $request->peer_id,
            'user_name' => $user?->name ?? 'សិស្ស Chill',
            'is_in_voice' => true,
            'is_muted' => false,
            'last_seen_at' => now(),
        ])->save();

        return response()->json($participant);
    }

    public function leaveVoice(Request $request, $roomId)
    {
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
        if ($request->filled('peer_id')) {
            RoomParticipant::where('room_id', $roomId)
                ->where('peer_id', $request->peer_id)
                ->update(['is_in_voice' => false, 'is_muted' => false, 'last_seen_at' => now()]);
        }

        return response()->json(['message' => 'Left voice']);
    }

    public function updateGoal(Request $request, $roomId)
    {
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
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
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
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
        $request->validate(['action' => ['required', 'in:accept,reject']]);
        $invite = RoomInvite::where('invitee_id', $request->user()->id)
            ->where('status', 'pending')
            ->findOrFail($inviteId);
        $invite->update(['status' => $request->input('action') === 'accept' ? 'accepted' : 'rejected']);

        if ($invite->status === 'accepted') {
            $user = $request->user();
            $participant = RoomParticipant::firstOrNew(['room_id' => $invite->room_id, 'user_id' => $user->id]);
            $participant->fill([
                'user_name' => $user->name,
                'study_goal' => $participant->study_goal === 'pending_approval'
                    ? 'រៀនផ្ដោតអារម្មណ៍ ២៥ នាទី'
                    : ($participant->study_goal ?: 'រៀនផ្ដោតអារម្មណ៍ ២៥ នាទី'),
                'last_seen_at' => now(),
            ])->save();
        }

        return response()->json(['status' => $invite->status, 'room_id' => $invite->room_id]);
    }

    public function requestJoin(Request $request, $roomId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $room = Room::findOrFail($roomId);
        if (($room->access_mode ?? 'pin') !== 'approval') {
            return response()->json(['message' => 'This room does not require approval.'], 422);
        }

        $participant = RoomParticipant::firstOrNew(['room_id' => $roomId, 'user_id' => $user->id]);
        $participant->user_name = $user->name;
        if (! $participant->exists || $participant->study_goal === 'pending_approval') {
            $participant->study_goal = 'pending_approval';
            $participant->is_in_voice = false;
        }
        $participant->last_seen_at = now();
        $participant->save();

        return response()->json([
            'requested' => $participant->study_goal === 'pending_approval',
            'approved' => $participant->study_goal !== 'pending_approval',
            'message' => 'សំណើត្រូវបានផ្ញើ!',
        ]);
    }

    public function checkJoinStatus(Request $request, $roomId)
    {
        $user = $request->user();
        $participant = RoomParticipant::where('room_id', $roomId)
            ->where('user_id', $user?->id ?? 0)
            ->first();

        return response()->json([
            'requested' => (bool) $participant,
            'approved' => (bool) ($participant && $participant->study_goal !== 'pending_approval'),
        ]);
    }

    public function approveMember(Request $request, $roomId, $participantId)
    {
        $room = Room::findOrFail($roomId);
        $user = $request->user();
        if (! $user || ((int) $room->creator_id !== (int) $user->id && (int) $user->id !== 1)) {
            return response()->json(['message' => 'Only the room admin can approve members.'], 403);
        }

        $request->validate(['action' => ['required', 'in:approve,reject']]);
        $participant = RoomParticipant::where('room_id', $roomId)->find($participantId);

        if (! $participant) {
            return response()->json(['message' => 'រកមិនឃើញសំណើ'], 404);
        }

        if ($request->input('action') === 'approve') {
            $participant->update(['study_goal' => 'រៀនផ្ដោតអារម្មណ៍ ២៥ នាទី']);
            return response()->json(['message' => 'បានអនុញ្ញាត!']);
        }

        $participant->delete();

        return response()->json(['message' => 'បានបដិសេធ!']);
    }
    // 🚪 កត់ត្រាវត្តមានសិស្សភ្លាមៗពេលទើបចូលបន្ទប់
    public function enterRoom(Request $request, $roomId)
    {
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated.'], 401);
        $userName = $user->name;

        $participant = RoomParticipant::firstOrNew([
            'room_id' => $roomId,
            'user_id' => $user->id,
        ]);

        $participant->fill([
            'user_name' => $userName,
            'peer_id' => $request->input('peer_id') ?: $participant->peer_id,
            'study_goal' => $participant->study_goal ?: 'រៀនផ្ដោតអារម្មណ៍ ២៥ នាទី',
            'is_in_voice' => $participant->is_in_voice ?? false,
            'last_seen_at' => now(),
        ])->save();

        return response()->json($participant);
    }

    public function leaveRoom(Request $request, $roomId)
    {
        if ($denied = $this->roomAccessError($request, $roomId)) return $denied;
        RoomParticipant::where('room_id', $roomId)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Left room.']);
    }
}
