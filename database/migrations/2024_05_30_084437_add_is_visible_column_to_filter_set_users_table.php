<?php

use Archilex\AdvancedTables\Models\Scopes\TenantScope;
use Archilex\AdvancedTables\Support\Config;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filament_filter_set_user', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true);
        });

        // Prior to v3, global favorites appeared for all users, including the
        // view's creator, regardless of whether or not the creator had toggled
        // on "make favorite".

        // v3 now allows global favorites to be hidden by users. This means that
        // if a user created a global favorite but didn't mark it as a favorite,
        // in v3 it will not appear in the favorites bar.

        // Therefore, we need to attach (make favorite) any view created by the
        // user that are global favorite, but aren't attached (favorited).

        Config::getUserView()::query()
            ->withoutGlobalScope(TenantScope::class)
            ->global()
            ->each(function ($view) {
                $view->userManagedUserViews()
                    ->syncWithPivotValues($view->user_id, [
                        'is_visible' => true,
                    ], false);
            });
    }

    public function down(): void
    {
        Schema::table('filament_filter_set_user', function (Blueprint $table) {
            $table->dropColumn('is_visible');
        });
    }
};
