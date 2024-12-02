<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
        // return $user->can('view_any_report');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
        return true;
        // return $user->can('view_report');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
        // return $user->can('create_report');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Report $report): bool
    {
        if (($user->can('update_report') && (($report->assigned_to == null || $report->assigned_to == $user->id) && ($report->status != 'approved') && ($report->status != 'rejected'))) || ($user->id == $report->user_id && $report->status == null)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Report $report): bool
    {
        return $user->can('delete_report');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_report');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return $user->can('force_delete_report');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_report');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Report $report): bool
    {
        return $user->can('restore_report');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_report');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Report $report): bool
    {
        return $user->can('replicate_report');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_report');
    }
}
