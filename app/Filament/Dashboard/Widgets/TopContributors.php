<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopContributors extends Widget
{
    protected static string $view = 'filament.dashboard.widgets.top-contributors';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getTopContributors(): array
    {
        return Cache::flexible('dashboard.top_contributors', [3600, 7200], function () {
            // Get top 10 contributors from audit trails, excluding COCONUT Curator (user ID 11)
            $contributors = DB::table('audits')
                ->select('user_id', DB::raw('COUNT(*) as contribution_count'))
                ->whereNotNull('user_id')
                ->where('user_id', '!=', 11) // Exclude COCONUT Curator
                ->groupBy('user_id')
                ->orderByDesc('contribution_count')
                ->limit(10)
                ->get();

            $contributorData = [];
            foreach ($contributors as $contributor) {
                $user = User::find($contributor->user_id);
                if ($user) {
                    $contributorData[] = [
                        'user' => $user,
                        'contribution_count' => $contributor->contribution_count,
                        'avatar_url' => $user->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=7F9CF5&background=EBF4FF',
                    ];
                }
            }

            return $contributorData;
        });
    }
}
