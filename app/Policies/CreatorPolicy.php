<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Creator;
use Illuminate\Auth\Access\HandlesAuthorization;

class CreatorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Creator');
    }

    public function view(AuthUser $authUser, Creator $creator): bool
    {
        return $authUser->can('View:Creator');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Creator');
    }

    public function update(AuthUser $authUser, Creator $creator): bool
    {
        return $authUser->can('Update:Creator');
    }

    public function delete(AuthUser $authUser, Creator $creator): bool
    {
        return $authUser->can('Delete:Creator');
    }

    public function restore(AuthUser $authUser, Creator $creator): bool
    {
        return $authUser->can('Restore:Creator');
    }

    public function forceDelete(AuthUser $authUser, Creator $creator): bool
    {
        return $authUser->can('ForceDelete:Creator');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Creator');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Creator');
    }

    public function replicate(AuthUser $authUser, Creator $creator): bool
    {
        return $authUser->can('Replicate:Creator');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Creator');
    }

}