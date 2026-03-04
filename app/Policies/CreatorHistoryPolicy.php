<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CreatorHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class CreatorHistoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CreatorHistory');
    }

    public function view(AuthUser $authUser, CreatorHistory $creatorHistory): bool
    {
        return $authUser->can('View:CreatorHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CreatorHistory');
    }

    public function update(AuthUser $authUser, CreatorHistory $creatorHistory): bool
    {
        return $authUser->can('Update:CreatorHistory');
    }

    public function delete(AuthUser $authUser, CreatorHistory $creatorHistory): bool
    {
        return $authUser->can('Delete:CreatorHistory');
    }

    public function restore(AuthUser $authUser, CreatorHistory $creatorHistory): bool
    {
        return $authUser->can('Restore:CreatorHistory');
    }

    public function forceDelete(AuthUser $authUser, CreatorHistory $creatorHistory): bool
    {
        return $authUser->can('ForceDelete:CreatorHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CreatorHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CreatorHistory');
    }

    public function replicate(AuthUser $authUser, CreatorHistory $creatorHistory): bool
    {
        return $authUser->can('Replicate:CreatorHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CreatorHistory');
    }

}