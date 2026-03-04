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
        $posts = Clothing::query();
        $test = $posts->where('Status', '=', 'Opgepakt')->where('Kleding_Deadline', '<=', Carbon::now())->toSql();
        //dd($test);
        return [


            Stat::make('Openstaande aanvragen', Clothing::all()->where('Status', '=', 'Aangeboden')->count()),
            Stat::make('Naderende deadline',  Clothing::all()->where('Status', '=', 'Opgepakt')->where('Kleding_Deadline', '<', Carbon::now()->addDay(7))->count()),

        ];
    }
}
