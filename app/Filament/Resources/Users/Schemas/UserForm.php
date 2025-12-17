<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('Email address'))
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label(__('Email Verified At')),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->required(),
                Textarea::make('two_factor_secret')
                    ->label(__('Two Factor Secret'))
                    ->columnSpanFull(),
                Textarea::make('two_factor_recovery_codes')
                    ->label(__('Two Factor Recovery Codes'))
                    ->columnSpanFull(),
                DateTimePicker::make('two_factor_confirmed_at')
                    ->label(__('Two Factor Confirmed At')),
            ]);
    }
}
