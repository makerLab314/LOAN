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
use Illuminate\Support\Str;
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

    /**
     * @param  string  $id  Schlüssel aus sessionKey(), nicht die Session-ID selbst
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (hash_equals(self::sessionKey($request->session()->getId()), $id)) {
            return back()->with('error', __('Die aktuelle Sitzung beendest du über „Abmelden“.'));
        }

        $sessionId = DB::table('sessions')->where('user_id', $user->id)->pluck('id')
            ->first(fn (string $sid) => hash_equals(self::sessionKey($sid), $id));

        if ($sessionId) {
            DB::table('sessions')->where('id', $sessionId)->delete();
            // Welches "Angemeldet bleiben"-Cookie zu der Sitzung gehört, ist nicht bekannt –
            // ohne neuen Token würde sich der Browser sofort wieder anmelden.
            self::cycleRememberToken($user);
            Audit::log('session.revoked', $user);
        }

        return back()->with('success', __('Sitzung beendet. „Angemeldet bleiben“ wurde auf allen Geräten zurückgesetzt.'));
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
        self::cycleRememberToken($user);

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
                'id' => self::sessionKey($s->id),
                'ip' => $s->ip_address,
                'agent' => UserAgent::parse($s->user_agent),
                'last_active' => Carbon::createFromTimestamp($s->last_activity)->setTimezone(config('app.timezone')),
                'current' => $s->id === $currentId,
            ]);
    }

    /**
     * Öffentlicher Schlüssel einer Sitzung (HMAC) – die Session-ID selbst landet nie im HTML.
     */
    public static function sessionKey(string $sessionId): string
    {
        return substr(hash_hmac('sha256', $sessionId, (string) config('app.key')), 0, 32);
    }

    /**
     * Neuer Remember-Token: macht alle "Angemeldet bleiben"-Cookies des Kontos ungültig.
     */
    private static function cycleRememberToken(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }
}
