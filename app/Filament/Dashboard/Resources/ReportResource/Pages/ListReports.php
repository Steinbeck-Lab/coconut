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
                    return $query->whereHas('curators', function ($q) {
                        $q->where('report_user.user_id', auth()->id());
                    })->whereNotIn('status', ['approved', 'rejected']);
                })
                ->favorite()
                ->badge(
                    Report::query()
                        ->whereHas('curators', function ($q) {
                            $q->where('report_user.user_id', auth()->id());
                        })
                        ->whereNotIn('status', ['approved', 'rejected'])
                        ->count()
                )
                ->preserveAll();
        }

        return $presetViews;
    }
}
