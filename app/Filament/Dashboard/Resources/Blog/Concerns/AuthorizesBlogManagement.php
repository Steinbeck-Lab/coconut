<?php

namespace App\Filament\Dashboard\Resources\Blog\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AuthorizesBlogManagement
{
    protected static function userCanManageBlog(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'dev']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::userCanManageBlog();
    }

    public static function canCreate(): bool
    {
        return static::userCanManageBlog();
    }

    public static function canEdit(Model $record): bool
    {
        return static::userCanManageBlog();
    }

    public static function canDelete(Model $record): bool
    {
        return static::userCanManageBlog();
    }
}
