<?php

namespace App\Filament\Resources\Clothing\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Clothing;
use Illuminate\Support\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Stat::make('Openstaande aanvragen', Clothing::where('Status', 'Aangeboden')->count()),
            Stat::make('Naderende deadline', Clothing::where('Status', 'Opgepakt')
                ->whereBetween('Kleding_Deadline', [Carbon::now(), Carbon::now()->addDays(7)])
                ->count()),
            Stat::make('Deadline verlopen', Clothing::where('Status', 'Opgepakt')
                ->where('Kleding_Deadline', '<', Carbon::now())
                ->count()),
        ];
    }
}
