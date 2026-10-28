<?php

namespace App\Filament\Resources\AuthenticationLogs\Tables;

use Filament\Tables\Table;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use App\Models\AuthenticationLog;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;

class AuthenticationLogsTable
{
    public static function configure(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('ip_address')
                    ->label('IP-adres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device_name')
                    ->label('Browser/apparaat')
                    ->searchable()
                    ->default('Onbekend apparaat'),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->label('Locatie')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('location->city', 'like', "%{$search}%")
                            ->orWhere('location->state', 'like', "%{$search}%")
                            ->orWhere('location->state_name', 'like', "%{$search}%")
                            ->orWhere('location->postal_code', 'like', "%{$search}%");
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state || ($state['default'] ?? false)) {
                            return '-';
                        }
                        return ($state['city'] ?? 'Onbekende plaats') . ', ' . ($state['state'] ?? 'Onbekende provincie');
                    }),
                TextColumn::make('device_name')
                    ->label('Apparaat')
                    ->default('Onbekend')
                    ->searchable(),
                IconColumn::make('login_successful')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                IconColumn::make('is_trusted')
                    ->label('Vertrouwd')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_suspicious')
                    ->label('Verdacht')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('warning')
                    ->sortable(),
/*                TextColumn::make('login_at')
                    ->label('Login At')
                    ->dateTime()
                    ->sortable()
                    ->default('-'),
                TextColumn::make('logout_at')
                    ->label('Logout At')
                    ->dateTime()
                    ->sortable()
                    ->default('-'),
                TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable()
                    ->default('-'),
*/
            ])
            ->filters([
                TernaryFilter::make('login_successful')
                    ->label('Inlogstatus')
                    ->placeholder('Alle inlogpogingen')
                    ->trueLabel('Alleen geslaagd')
                    ->falseLabel('Alleen mislukt'),
                TernaryFilter::make('is_trusted')
                    ->label('Vertrouwd apparaat')
                    ->placeholder('Alle apparaten')
                    ->trueLabel('Alleen vertrouwd')
                    ->falseLabel('Alleen niet vertrouwd'),
                TernaryFilter::make('is_suspicious')
                    ->label('Verdachte activiteit')
                    ->placeholder('Alle activiteiten')
                    ->trueLabel('Alleen verdacht')
                    ->falseLabel('Alleen normaal'),
                Filter::make('active_sessions')
                    ->label('Actieve sessies')
                    ->query(
                        fn(Builder $query): Builder => $query
                            ->where('login_successful', true)
                            ->whereNull('logout_at')
                    ),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Details bekijken')
                    ->icon('heroicon-o-eye')
                    ->modalContent(function (AuthenticationLog $record) {
                        return view('filament.resources.authentication-log.view', [
                            'record' => $record,
                        ]);
                    })
                    ->modalHeading('Details inlogpoging'),
            ])
            ->defaultSort('login_at', 'desc')
            ->poll('30s');
    }

}
