<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    // ទាញយកបញ្ជីឯកសារ (អាច Filter តាម grade_level ឬ subject)
    public function index(Request $request)
    {
        $query = Document::query();

        if ($request->has('grade_level') && $request->grade_level !== 'all') {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('subject') && $request->subject !== 'all') {
            $query->where('subject', $request->subject);
        }

        return response()->json($query->latest()->get());
    }

    // ចុចទាញយកឯកសារ (កាត់ PTS បើជាឯកសារ Premium)
    public function download(Request $request, Document $document)
    {
        $user = $request->user();
        $result = DB::transaction(function () use ($document, $user) {
            $lockedDocument = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $lockedUser = $user ? $user->newQuery()->whereKey($user->id)->lockForUpdate()->first() : null;

            if ($lockedDocument->pts_cost > 0 && (! $lockedUser || $lockedUser->coins < $lockedDocument->pts_cost)) {
                return ['error' => 'Not enough points to download this document.', 'status' => 400];
            }

            if ($lockedDocument->pts_cost > 0) {
                $lockedUser->coins -= $lockedDocument->pts_cost;
                $lockedUser->save();
            }

            $lockedDocument->increment('downloads_count');

            return [
                'document' => $lockedDocument,
                'remaining_coins' => $lockedUser?->coins ?? 0,
            ];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        return response()->json([
            'message' => 'Download ready.',
            'file_url' => $result['document']->file_url,
            'remaining_coins' => $result['remaining_coins'],
        ]);
    }
}
