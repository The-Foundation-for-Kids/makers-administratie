<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Collector;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Collector');
    }

    public function view(AuthUser $authUser, Collector $collector): bool
    {
        return $authUser->can('View:Collector');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Collector');
    }

    public function update(AuthUser $authUser, Collector $collector): bool
    {
        return $authUser->can('Update:Collector');
    }

    public function delete(AuthUser $authUser, Collector $collector): bool
    {
        return $authUser->can('Delete:Collector');
    }

    public function restore(AuthUser $authUser, Collector $collector): bool
    {
        return $authUser->can('Restore:Collector');
    }

    public function forceDelete(AuthUser $authUser, Collector $collector): bool
    {
        return $authUser->can('ForceDelete:Collector');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Collector');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Collector');
    }

    public function replicate(AuthUser $authUser, Collector $collector): bool
    {
        return $authUser->can('Replicate:Collector');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Collector');
    }

}