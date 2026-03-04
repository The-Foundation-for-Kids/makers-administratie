<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Clothing;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClothingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Clothing');
    }

    public function view(AuthUser $authUser, Clothing $clothing): bool
    {
        return $authUser->can('View:Clothing');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Clothing');
    }

    public function update(AuthUser $authUser, Clothing $clothing): bool
    {
        return $authUser->can('Update:Clothing');
    }

    public function delete(AuthUser $authUser, Clothing $clothing): bool
    {
        return $authUser->can('Delete:Clothing');
    }

    public function restore(AuthUser $authUser, Clothing $clothing): bool
    {
        return $authUser->can('Restore:Clothing');
    }

    public function forceDelete(AuthUser $authUser, Clothing $clothing): bool
    {
        return $authUser->can('ForceDelete:Clothing');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Clothing');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Clothing');
    }

    public function replicate(AuthUser $authUser, Clothing $clothing): bool
    {
        return $authUser->can('Replicate:Clothing');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Clothing');
    }

}