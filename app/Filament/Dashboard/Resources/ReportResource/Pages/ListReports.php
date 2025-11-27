<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

use Filament\Actions\CreateAction;
use App\Enums\ReportStatus;
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
            CreateAction::make(),
        ];
    }

    public function getPresetViews(): array
    {
        $presetViews = [
            'on going' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->whereIn('status', [ReportStatus::SUBMITTED->value, ReportStatus::PENDING_APPROVAL->value, ReportStatus::PENDING_REJECTION->value]))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->isCurator()) {
                        return Report::query()->whereIn('status', [ReportStatus::SUBMITTED->value, ReportStatus::PENDING_APPROVAL->value, ReportStatus::PENDING_REJECTION->value])->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->whereIn('status', [ReportStatus::SUBMITTED->value, ReportStatus::PENDING_APPROVAL->value, ReportStatus::PENDING_REJECTION->value])->count();
                })
                ->preserveAll()
                ->default(),
            'approved' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', ReportStatus::APPROVED->value))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->isCurator()) {
                        return Report::query()->where('status', ReportStatus::APPROVED->value)->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', ReportStatus::APPROVED->value)->count();
                })
                ->preserveAll(),
            'rejected' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', ReportStatus::REJECTED->value))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->isCurator()) {
                        return Report::query()->where('status', ReportStatus::REJECTED->value)->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', ReportStatus::REJECTED->value)->count();
                })
                ->preserveAll(),
        ];
        if (auth()->user()->isCurator()) {
            $presetViews['assigned to me'] = PresetView::make()
                ->modifyQueryUsing(function ($query) {
                    // We want reports that are actionable by the current curator
                    return $query->where(function ($subquery) {
                        // Case 1: Reports where the user is assigned as curator 1 and status is 'submitted'
                        $subquery->where(function ($q) {
                            $q->whereHas('curators', function ($curatorQuery) {
                                $curatorQuery->where('report_user.user_id', auth()->id())
                                    ->where('report_user.curator_number', 1);
                            })->where('status', ReportStatus::SUBMITTED->value);
                        });

                        // Case 2: Reports waiting for second curator review and:
                        // - current user is assigned as curator 2, OR
                        // - current user is not the first curator and no curator 2 is assigned yet
                        $subquery->orWhere(function ($q) {
                            $q->whereIn('status', [ReportStatus::PENDING_APPROVAL->value, ReportStatus::PENDING_REJECTION->value])
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
                                })->where('status', ReportStatus::SUBMITTED->value);
                            });

                            // Case 2: Reports waiting for second curator review and:
                            // - current user is assigned as curator 2, OR
                            // - current user is not the first curator and no curator 2 is assigned yet
                            $subquery->orWhere(function ($q) {
                                $q->whereIn('status', [ReportStatus::PENDING_APPROVAL->value, ReportStatus::PENDING_REJECTION->value])
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
