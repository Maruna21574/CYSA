<?php

namespace App\Http\Controllers\Student;

use App\Gamification\GamificationService;
use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\XpTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function __invoke(Request $request, GamificationService $gamification): View
    {
        $user = $request->user();
        abort_unless($gamification->enabledFor($user), 404);

        $earned = DB::table('user_badges')->where('user_id', $user->id)->pluck('awarded_at', 'badge_id');

        return view('student.achievements', [
            'stats' => $gamification->stats($user),
            'badges' => Badge::orderBy('id')->get(),
            'earned' => $earned,
            'history' => XpTransaction::where('user_id', $user->id)->latest('id')->limit(20)->get(),
        ]);
    }
}
