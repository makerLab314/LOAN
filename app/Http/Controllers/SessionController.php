<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use App\Support\UserAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Übersicht der aktiven Sitzungen (Geräte/Browser) mit Abmelden.
 * Setzt SESSION_DRIVER=database voraus.
 */
class SessionController extends Controller
{
    public function index(Request $request): View
    {
        return view('profile.sessions', [
            'sessions' => self::sessionsFor($request->user(), $request->session()->getId()),
            'supported' => config('session.driver') === 'database',
        ]);
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        if ($id === $request->session()->getId()) {
            return back()->with('error', __('Die aktuelle Sitzung beendest du über „Abmelden“.'));
        }

        $deleted = DB::table('sessions')->where('id', $id)->where('user_id', $request->user()->id)->delete();
        if ($deleted) {
            Audit::log('session.revoked', $request->user());
        }

        return back()->with('success', __('Sitzung beendet.'));
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('sessions', [
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($data['password']);
        DB::table('sessions')->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())->delete();

        Audit::log('session.revoked_others', $request->user());

        return back()->with('success', __('Alle anderen Sitzungen wurden beendet.'));
    }

    /**
     * Admin: alle Sitzungen eines Nutzers beenden.
     */
    public function destroyForUser(Request $request, User $user): RedirectResponse
    {
        DB::table('sessions')->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())->delete();

        Audit::log('session.revoked_by_admin', $user);

        return back()->with('success', __('Alle Sitzungen von :name wurden beendet.', ['name' => $user->name]));
    }

    /**
     * Je Sitzung: id, ip, agent (browser/os/mobile), last_active, current.
     *
     * @return Collection<int, mixed>
     */
    public static function sessionsFor(User $user, ?string $currentId = null): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($s) => (object) [
                'id' => $s->id,
                'ip' => $s->ip_address,
                'agent' => UserAgent::parse($s->user_agent),
                'last_active' => Carbon::createFromTimestamp($s->last_activity)->setTimezone(config('app.timezone')),
                'current' => $s->id === $currentId,
            ]);
    }
}
