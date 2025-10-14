<?php

namespace App\Policies;

use App\Models\ProductionTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductionTaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductionTask $productionTask): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('dispatcher');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('dispatcher')) {
            return $productionTask->status === 'pending';
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductionTask $productionTask): bool
    {
        return $user->hasRole('admin');
    }
    public function take(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasRole('master') && $productionTask->status === 'pending';
    }
    public function updateComponents(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('master')) {
            return $productionTask->status === 'in_process';
        }
        return false;
    }
    public function sendForInspection(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('master')) {
            return $productionTask->status === 'in_process';
        }
        return false;
    }
    public function acceptByOTK(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasRole('otk') && $productionTask->status === 'checking';
    }
    public function rejectByOTK(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasRole('otk') && $productionTask->status === 'checking';
    }
    public function returnToMaster(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->hasRole('otk') && $productionTask->status === 'checking';
    }
    public function addComponent(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('master')) {
            return $productionTask->status === 'in_process';
        }
        return false;
    }
    public function removeComponent(User $user, ProductionTask $productionTask): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('master')) {
            return $productionTask->status === 'in_process';
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProductionTask $productionTask): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProductionTask $productionTask): bool
    {
        return $user->hasRole('admin');
    }
}
