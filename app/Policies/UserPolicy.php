<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Schutz vor Aussperren: der letzte Admin kann weder gelöscht noch herabgestuft
 * werden, und niemand löscht oder degradiert sich in der Nutzerverwaltung selbst.
 */
class UserPolicy
{
    public function delete(User $actor, User $target): Response
    {
        if (! $actor->can('users.manage')) {
            return Response::deny();
        }
        if ($actor->is($target)) {
            return Response::deny(__('Du kannst dein eigenes Konto hier nicht löschen.'));
        }
        if ($target->isAdmin() && User::activeAdminCount() <= 1) {
            return Response::deny(__('Der letzte Admin kann nicht gelöscht werden.'));
        }

        return Response::allow();
    }

    public function changeRole(User $actor, User $target, string $newRole): Response
    {
        if (! $actor->can('users.manage')) {
            return Response::deny();
        }
        if ($target->role === $newRole) {
            return Response::allow();
        }
        if ($actor->is($target)) {
            return Response::deny(__('Du kannst deine eigene Rolle nicht ändern.'));
        }
        if ($target->isAdmin() && User::activeAdminCount() <= 1) {
            return Response::deny(__('Der letzte Admin kann nicht herabgestuft werden.'));
        }

        return Response::allow();
    }
}
