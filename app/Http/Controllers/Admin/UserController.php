<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SessionController;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('name')->get();

        $lastSeen = config('session.driver') === 'database'
            ? DB::table('sessions')->whereNotNull('user_id')->groupBy('user_id')
                ->selectRaw('user_id, MAX(last_activity) as last_activity')->pluck('last_activity', 'user_id')
            : collect();

        return view('admin.users.index', compact('users', 'lastSeen'));
    }

    public function create(): View
    {
        $user = new User(['locale' => 'de']);
        $user->role = User::ROLE_MODERATOR;

        return view('admin.users.form', ['user' => $user, 'sessions' => collect()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::ROLES)],
            'locale' => ['required', Rule::in(['de', 'en'])],
            'must_change_password' => ['nullable', 'boolean'],
        ], [], $this->attributeNames());

        // Gelöschtes Konto mit derselben E-Mail wiederherstellen (E-Mail ist in der DB eindeutig,
        // und so bleiben frühere Ausleihen/Buchungen dem Konto zugeordnet)
        $user = User::onlyTrashed()->where('email', $data['email'])->first() ?? new User;
        $restored = $user->exists;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'locale' => $data['locale'],
        ]);
        $user->role = $data['role'];
        // Standard: bei der ersten Anmeldung ein eigenes Passwort verlangen (Kiosk ausgenommen)
        $user->must_change_password = $request->boolean('must_change_password', true) && $user->role !== User::ROLE_KIOSK;
        $user->remember_token = Str::random(60);
        $user->deleted_at = null;
        $user->save();

        Audit::log($restored ? 'user.restored' : 'user.created', $user, ['role' => $user->role, 'must_change_password' => $user->must_change_password]);

        return redirect()->route('admin.users.index')->with('success', $restored ? __('Gelöschtes Konto wiederhergestellt.') : __('Nutzer angelegt.'));
    }

    public function edit(Request $request, User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'sessions' => SessionController::sessionsFor($user, $request->session()->getId()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::ROLES)],
            'locale' => ['required', Rule::in(['de', 'en'])],
            'must_change_password' => ['nullable', 'boolean'],
        ], [], $this->attributeNames());

        $this->authorize('changeRole', [$user, $data['role']]);

        $roleChanged = $user->role !== $data['role'];
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'locale' => $data['locale'],
        ]);
        $user->role = $data['role'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->remember_token = Str::random(60);
            // Neues Passwort vom Admin: beim nächsten Anmelden ein eigenes verlangen (nicht beim eigenen Konto)
            $user->must_change_password = $request->boolean('must_change_password', true) && ! $user->is($request->user());
            // Vom Admin gesetztes Passwort: alle Sitzungen des Nutzers beenden
            // (bei sich selbst bis auf die aktuelle)
            DB::table('sessions')->where('user_id', $user->id)
                ->when($user->is($request->user()), fn ($q) => $q->where('id', '!=', $request->session()->getId()))
                ->delete();
        }

        if ($user->isKiosk()) {
            $user->must_change_password = false;
        }

        $user->save();

        // Eigenes Konto: den angemeldeten Nutzer aktualisieren, damit AuthenticateSession
        // den neuen Passwort-Hash speichert und die aktuelle Sitzung nicht beendet
        if ($user->is($request->user())) {
            Auth::guard('web')->setUser($user);
        }

        Audit::log('user.updated', $user, array_filter([
            'role' => $roleChanged ? $user->role : null,
            'password_reset' => ! empty($data['password']) ?: null,
            'must_change_password' => ! empty($data['password']) && $user->must_change_password ?: null,
        ]));

        return redirect()->route('admin.users.index')->with('success', __('Nutzer gespeichert.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->delete();
        Audit::log('user.deleted', $user);

        return redirect()->route('admin.users.index')->with('success', __('Nutzer gelöscht.'));
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'name' => __('Name'),
            'email' => __('E-Mail'),
            'password' => __('Passwort'),
            'role' => __('Rolle'),
        ];
    }
}
