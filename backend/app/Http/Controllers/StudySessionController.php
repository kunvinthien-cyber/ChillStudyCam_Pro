<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Campus;
use Illuminate\Http\Request;

class StudySessionController extends Controller
{
    public function complete(Request $request)
    {
        $user = $request->user() ?? User::first();
        $addedHours = round(25 / 60, 2); // ~0.42 ម៉ោង

        if ($user) {
            $user->coins += 25;
            $user->studied_hours = round($user->studied_hours + $addedHours, 2);
            $user->save();

            // 🏆 បូកម៉ោងចូលសាកលវិទ្យាល័យរបស់សិស្ស (ឧ. RUPP ជាលំនាំដើម)
            $campus = Campus::first();
            if ($campus) {
                $campus->total_hours += $addedHours;
                $campus->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'អបអរសាទរ! អ្នកបានបញ្ចប់ការរៀនដោយជោគជ័យ 🎉',
            'reward' => [
                'earned_coins' => 25,
                'total_coins' => $user ? $user->coins : 0,
                'studied_hours' => $user ? $user->studied_hours : 0,
            ]
        ]);
    }

    // API ទាញយកចំណាត់ថ្នាក់ Campus Cup
    public function getLeaderboard()
    {
        $leaderboard = Campus::orderBy('total_hours', 'desc')->get();
        return response()->json($leaderboard);
    }
}
