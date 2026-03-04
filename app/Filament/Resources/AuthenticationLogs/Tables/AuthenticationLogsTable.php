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
                    ->label('IP Address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device_name')
                    ->label('Browser/Device')
                    ->searchable()
                    ->default('Unknown Device'),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->label('Location')
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
                        return ($state['city'] ?? 'Unknown City') . ', ' . ($state['state'] ?? 'Unknown State');
                    }),
                TextColumn::make('device_name')
                    ->label('Device')
                    ->default('Unknown')
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
                    ->label('Trusted')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_suspicious')
                    ->label('Suspicious')
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
                    ->label('Login Status')
                    ->placeholder('All logins')
                    ->trueLabel('Successful only')
                    ->falseLabel('Failed only'),
                TernaryFilter::make('is_trusted')
                    ->label('Trusted Device')
                    ->placeholder('All devices')
                    ->trueLabel('Trusted only')
                    ->falseLabel('Untrusted only'),
                TernaryFilter::make('is_suspicious')
                    ->label('Suspicious Activity')
                    ->placeholder('All activities')
                    ->trueLabel('Suspicious only')
                    ->falseLabel('Normal only'),
                Filter::make('active_sessions')
                    ->label('Active Sessions')
                    ->query(
                        fn(Builder $query): Builder => $query
                            ->where('login_successful', true)
                            ->whereNull('logout_at')
                    ),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->modalContent(function (AuthenticationLog $record) {
                        return view('filament.resources.authentication-log.view', [
                            'record' => $record,
                        ]);
                    })
                    ->modalHeading('Authentication Log Details'),
            ])
            ->defaultSort('login_at', 'desc')
            ->poll('30s');
    }

}
