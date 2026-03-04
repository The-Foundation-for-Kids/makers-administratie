<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\UserInvitation;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserInvitationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UserInvitation');
    }

    public function view(AuthUser $authUser, UserInvitation $userInvitation): bool
    {
        return $authUser->can('View:UserInvitation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UserInvitation');
    }

    public function update(AuthUser $authUser, UserInvitation $userInvitation): bool
    {
        return $authUser->can('Update:UserInvitation');
    }

    public function delete(AuthUser $authUser, UserInvitation $userInvitation): bool
    {
        return $authUser->can('Delete:UserInvitation');
    }

    public function restore(AuthUser $authUser, UserInvitation $userInvitation): bool
    {
        return $authUser->can('Restore:UserInvitation');
    }

    public function forceDelete(AuthUser $authUser, UserInvitation $userInvitation): bool
    {
        return $authUser->can('ForceDelete:UserInvitation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UserInvitation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UserInvitation');
    }

    public function replicate(AuthUser $authUser, UserInvitation $userInvitation): bool
    {
        return $authUser->can('Replicate:UserInvitation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UserInvitation');
    }

}