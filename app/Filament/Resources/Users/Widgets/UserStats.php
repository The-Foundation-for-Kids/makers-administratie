<?php

namespace App\Filament\Resources\Users\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class UserStats extends BaseWidget
{
    protected function getCards(): array
    {

        //       $test  =  Role::where('name','Maakster')->users();
        $maaksters = Role::findByName('Maakster')->users()->count();

        return [

            Stat::make('Maaksters', $maaksters),


        ];
    }
}
