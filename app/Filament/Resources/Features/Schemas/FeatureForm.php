<?php

namespace App\Filament\Resources\Features\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Basic Information'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Feature Name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g. Pro Reports')),

                        TextInput::make('code')
                            ->label(__('Internal Code'))
                            ->helperText(__('Unique technical identifier, e.g. "flying_stars"'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label(__('Description'))
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('order')
                            ->label(__('Order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(999),

                        Select::make('included_by_id')
                            ->label(__('Included in Module'))
                            ->helperText(__('If a user owns the selected module, they automatically have access to this one.'))
                            ->relationship('includedBy', 'name')
                            ->searchable()
                            ->nullable(),
                    ]),

                Section::make(__('Pricing & Status'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('price_netto')
                            ->label(__('Price (Net)'))
                            ->numeric()
                            ->default(0)
                            ->suffix(__('Cents'))
                            ->helperText(__('1900 = 19.00 EUR'))
                            ->required(),

                        TextInput::make('currency')
                            ->label(__('Currency'))
                            ->default(__('EUR'))
                            ->maxLength(3)
                            ->required(),

                        Toggle::make('active')
                            ->label(__('Active / Saleable'))
                            ->default(true)
                            ->inline(false)
                            ->helperText(__('Disabled features cannot be purchased anymore.')),

                        Toggle::make('is_default')
                            ->label(__('Is Default'))
                            ->inline(false)
                            ->helperText(__('Will be assigned to any new user upon user creation.'))
                            ->default(false),
                    ]),

                Section::make(__('Purchase & Quota'))
                    ->columns(3)
                    ->schema([
                        Select::make('purchase_type')
                            ->label(__('Purchase Type'))
                            ->options([
                                'lifetime' => __('Lifetime'),
                                'subscription' => __('Subscription'),
                            ])
                            ->default('lifetime')
                            ->required(),

                        Select::make('renewal_period')
                            ->label(__('Renewal Period'))
                            ->options([
                                'monthly' => __('Monthly'),
                                'yearly' => __('Yearly'),
                            ])
                            ->helperText(__('Only for subscription type.'))
                            ->nullable(),

                        TextInput::make('default_quota')
                            ->label(__('Default Quota'))
                            ->numeric()
                            ->helperText(__('e.g., number of family members.'))
                            ->nullable(),
                    ]),
            ]);
    }
}
