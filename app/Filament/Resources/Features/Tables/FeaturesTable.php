<?php

namespace App\Filament\Resources\Features\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Feature Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label(__('Code'))
                    ->searchable()
                    ->color('gray')
                    ->fontFamily('mono'),

                TextColumn::make('price_netto')
                    ->label(__('Price'))
                    ->money('EUR', divideBy: 100)
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('currency')
                    ->label(__('Currency'))
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('active')
                    ->label(__('Active'))
                    ->boolean(),


                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Später könnten wir hier filtern: ->query(fn ($q) => $q->where('active', true))
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
