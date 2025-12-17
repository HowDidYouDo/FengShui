<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'features';

    protected static ?string $title = 'Licenses & Features';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('feature_id')
                    ->label(__('Select Feature'))
                    ->relationship('feature', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    // Achtung: Bei RelationManagern in V4 kann es sein, dass
                    // disableOptionsWhenSelectedInSiblingRepeaterItems() nicht existiert oder anders heißt.
                    // Ich lasse es sicherheitshalber drin, falls Fehler kommt -> rausnehmen.
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                TextInput::make('quota')
                    ->label(__('Quota / Limit'))
                    ->numeric()
                    ->placeholder(__('e.g. 50 (Leave empty for unlimited)')),

                DatePicker::make('expires_at')
                    ->label(__('Expires At'))
                    ->native(false),

                Toggle::make('active')
                    ->label(__('Active'))
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('feature.name')
                    ->label(__('Feature'))
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quota')
                    ->label(__('Quota')),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('Expires At'))
                    ->date(),

                Tables\Columns\IconColumn::make('active')
                    ->label(__('Active'))
                    ->boolean(),
            ])
            // Änderung: Nutze headerActions für den "Create"-Button über der Tabelle
            ->headerActions([
                CreateAction::make()->label(__('Assign Feature')),
            ])
            // Änderung: Nutze recordActions statt actions (siehe Screenshot Warnung)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            // BulkActions bleiben meist so, falls er meckert, probieren wir es ohne
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
