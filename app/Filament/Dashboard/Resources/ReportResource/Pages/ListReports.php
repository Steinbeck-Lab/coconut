<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

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
            Actions\CreateAction::make(),
        ];
    }

    public function getPresetViews(): array
    {
        $presetViews = [
            'submitted' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', ReportStatus::SUBMITTED->value))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->roles()->exists()) {
                        return Report::query()->where('status', ReportStatus::SUBMITTED->value)->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', ReportStatus::SUBMITTED->value)->count();
                })
                ->preserveAll()
                ->default(),
            'approved' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', ReportStatus::APPROVED->value))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->roles()->exists()) {
                        return Report::query()->where('status', ReportStatus::APPROVED->value)->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', ReportStatus::APPROVED->value)->count();
                })
                ->preserveAll(),
            'rejected' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', ReportStatus::REJECTED->value))
                ->favorite()
                ->badge(function () {
                    if (auth()->user()->roles()->exists()) {
                        return Report::query()->where('status', ReportStatus::REJECTED->value)->count();
                    }

                    return Report::query()->where('user_id', auth()->id())->where('status', ReportStatus::REJECTED->value)->count();
                })
                ->preserveAll(),
        ];
        if (auth()->user()->roles()->exists()) {
            $presetViews['assigned'] = PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('assigned_to', auth()->id())->where('status', ReportStatus::SUBMITTED->value))
                ->favorite()
                ->badge(Report::query()->where('assigned_to', auth()->id())->where('status', ReportStatus::SUBMITTED->value)->count())
                ->preserveAll();
        }

        return $presetViews;
    }
}
