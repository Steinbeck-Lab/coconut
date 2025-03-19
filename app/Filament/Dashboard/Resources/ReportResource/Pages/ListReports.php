<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

use App\Filament\Dashboard\Resources\ReportResource;
use App\Models\Report;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReports extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getPresetViews(): array
    {
        $presetViews = [
            'submitted' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'submitted'))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->roles()->exists()) {
                        return Report::query()->where('status', 'submitted')->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', 'submitted')->count();
                })
                ->preserveAll()
                ->default(),
            'pending' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending_approval')->orWhere('status', 'pending_rejection'))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->roles()->exists()) {
                        return Report::query()->where('status', 'pending_approval')->orWhere('status', 'pending_rejection')->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', 'pending_approval')->orWhere('status', 'pending_rejection')->count();
                })
                ->preserveAll()
                ->default(),
            'approved' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'approved'))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->roles()->exists()) {
                        return Report::query()->where('status', 'approved')->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', 'approved')->count();
                })
                ->preserveAll(),
            'rejected' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'rejected'))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->roles()->exists()) {
                        return Report::query()->where('status', 'rejected')->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', 'rejected')->count();
                })
                ->preserveAll(),
        ];
        if (auth()->user()->roles()->exists()) {
            $presetViews['assigned'] = PresetView::make()
                ->modifyQueryUsing(function ($query) {
                    // We want reports that are actionable by the current curator
                    return $query->where(function ($subquery) {
                        // Case 1: Reports where the user is assigned as curator 1 and status is 'submitted'
                        $subquery->where(function ($q) {
                            $q->whereHas('curators', function ($curatorQuery) {
                                $curatorQuery->where('report_user.user_id', auth()->id())
                                    ->where('report_user.curator_number', 1);
                            })->where('status', 'submitted');
                        });

                        // Case 2: Reports waiting for second curator review and:
                        // - current user is assigned as curator 2, OR
                        // - current user is not the first curator and no curator 2 is assigned yet
                        $subquery->orWhere(function ($q) {
                            $q->whereIn('status', ['pending_approval', 'pending_rejection'])
                                ->where(function ($innerQuery) {
                                    // Current user is already assigned as curator 2
                                    $innerQuery->whereHas('curators', function ($curatorQuery) {
                                        $curatorQuery->where('report_user.user_id', auth()->id())
                                            ->where('report_user.curator_number', 2);
                                    });
                                });
                        });
                    });
                })
                ->favorite()
                ->badge(function () {
                    // Count reports that need the curator's attention
                    return Report::query()
                        ->where(function ($subquery) {
                            // Case 1: Reports where the user is assigned as curator 1 and status is 'submitted'
                            $subquery->where(function ($q) {
                                $q->whereHas('curators', function ($curatorQuery) {
                                    $curatorQuery->where('report_user.user_id', auth()->id())
                                        ->where('report_user.curator_number', 1);
                                })->where('status', 'submitted');
                            });

                            // Case 2: Reports waiting for second curator review and:
                            // - current user is assigned as curator 2, OR
                            // - current user is not the first curator and no curator 2 is assigned yet
                            $subquery->orWhere(function ($q) {
                                $q->whereIn('status', ['pending_approval', 'pending_rejection'])
                                    ->where(function ($innerQuery) {
                                        // Current user is already assigned as curator 2
                                        $innerQuery->whereHas('curators', function ($curatorQuery) {
                                            $curatorQuery->where('report_user.user_id', auth()->id())
                                                ->where('report_user.curator_number', 2);
                                        });
                                    });
                            });
                        })
                        ->count();
                })
                ->preserveAll();
        }

        return $presetViews;
    }
}
