<?php

namespace App\Policies;

use App\Enums\ReportStatus;
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
        // Allow users with update_report permission in these cases:
        if ($user->can('update_report')) {
            // Case 1: Report is submitted
            if ($report->status == ReportStatus::SUBMITTED->value) {
                // Allow if no curator 1 is assigned yet
                if (! $report->curators()->wherePivot('curator_number', 1)->exists()) {
                    return true;
                }

                // Or if user is already assigned as curator 1
                /** @var User|null $curator1 */
                $curator1 = $report->curators()->wherePivot('curator_number', 1)->first();
                if ($curator1?->id == $user->id) {
                    return true;
                }
            }

            // Case 2: Report is pending approval/rejection
            if ($report->status == ReportStatus::PENDING_APPROVAL->value || $report->status == ReportStatus::PENDING_REJECTION->value) {
                // Get the first curator ID
                /** @var User|null $firstCurator */
                $firstCurator = $report->curators()->wherePivot('curator_number', 1)->first();
                $firstCuratorId = $firstCurator?->id;

                // Don't allow if user was the first curator (enforce four-eyes principle)
                if ($firstCuratorId == $user->id) {
                    return false;
                }

                // Allow if no curator 2 is assigned yet
                if (! $report->curators()->wherePivot('curator_number', 2)->exists()) {
                    return true;
                }

                // Or if user is already assigned as curator 2
                /** @var User|null $curator2 */
                $curator2 = $report->curators()->wherePivot('curator_number', 2)->first();
                if ($curator2?->id == $user->id) {
                    return true;
                }
            }
        }

        // Allow report creator to update only if status is null (report is being created)
        if ($user->id == $report->user_id && $report->status == null) {
            return true;
        }

        return false;
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
