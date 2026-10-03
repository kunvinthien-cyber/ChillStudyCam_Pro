<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

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

        if ($document->pts_cost > 0) {
            if (!$user || $user->coins < $document->pts_cost) {
                return response()->json(['message' => 'កាក់ PTS របស់អ្នកមិនគ្រប់គ្រាន់ដើម្បីដោះសោឯកសារនេះឡើយ!'], 400);
            }

            // កាត់កាក់ PTS
            $user->coins -= $document->pts_cost;
            $user->save();
        }

        // បូកចំនួន Download
        $document->increment('downloads_count');

        return response()->json([
            'message' => 'ដោះសោជោគជ័យ!',
            'file_url' => $document->file_url,
            'remaining_coins' => $user ? $user->coins : 0
        ]);
    }
}
