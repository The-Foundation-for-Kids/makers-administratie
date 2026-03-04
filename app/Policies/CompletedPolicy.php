<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Completed;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompletedPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Completed');
    }

    public function view(AuthUser $authUser, Completed $completed): bool
    {
        return $authUser->can('View:Completed');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Completed');
    }

    public function update(AuthUser $authUser, Completed $completed): bool
    {
        return $authUser->can('Update:Completed');
    }

    public function delete(AuthUser $authUser, Completed $completed): bool
    {
        return $authUser->can('Delete:Completed');
    }

    public function restore(AuthUser $authUser, Completed $completed): bool
    {
        return $authUser->can('Restore:Completed');
    }

    public function forceDelete(AuthUser $authUser, Completed $completed): bool
    {
        return $authUser->can('ForceDelete:Completed');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Completed');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Completed');
    }

    public function replicate(AuthUser $authUser, Completed $completed): bool
    {
        return $authUser->can('Replicate:Completed');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Completed');
    }

}