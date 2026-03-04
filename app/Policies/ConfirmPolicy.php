<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\User;
use App\Models\Confirm;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConfirmPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param User $user
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('view_any_confirm');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param Confirm $confirm
     * @return Response|bool
     */
    public function view(User $user, Confirm $confirm)
    {
        return $user->can('view_confirm');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return Response|bool
     */
    public function create(User $user)
    {
        return $user->can('create_confirm');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param Confirm $confirm
     * @return Response|bool
     */
    public function update(User $user, Confirm $confirm)
    {
        return $user->can('update_confirm');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param Confirm $confirm
     * @return Response|bool
     */
    public function delete(User $user, Confirm $confirm)
    {
        return $user->can('delete_confirm');
    }

    /**
     * Determine whether the user can bulk delete.
     *
     * @param User $user
     * @return Response|bool
     */
    public function deleteAny(User $user)
    {
        return $user->can('delete_any_confirm');
    }

    /**
     * Determine whether the user can permanently delete.
     *
     * @param User $user
     * @param Confirm $confirm
     * @return Response|bool
     */
    public function forceDelete(User $user, Confirm $confirm)
    {
        return $user->can('force_delete_confirm');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     *
     * @param User $user
     * @return Response|bool
     */
    public function forceDeleteAny(User $user)
    {
        return $user->can('force_delete_any_confirm');
    }

    /**
     * Determine whether the user can restore.
     *
     * @param User $user
     * @param Confirm $confirm
     * @return Response|bool
     */
    public function restore(User $user, Confirm $confirm)
    {
        return $user->can('restore_confirm');
    }

    /**
     * Determine whether the user can bulk restore.
     *
     * @param User $user
     * @return Response|bool
     */
    public function restoreAny(User $user)
    {
        return $user->can('restore_any_confirm');
    }

    /**
     * Determine whether the user can replicate.
     *
     * @param User $user
     * @param Confirm $confirm
     * @return Response|bool
     */
    public function replicate(User $user, Confirm $confirm)
    {
        return $user->can('{{ Replicate }}');
    }

    /**
     * Determine whether the user can reorder.
     *
     * @param User $user
     * @return Response|bool
     */
    public function reorder(User $user)
    {
        return $user->can('{{ Reorder }}');
    }
}
