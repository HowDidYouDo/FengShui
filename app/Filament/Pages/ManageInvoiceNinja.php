<?php

namespace App\Filament\Pages;

use App\Models\InvoiceNinjaConfig;
use App\Services\InvoiceNinjaService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Exceptions\Halt;

class ManageInvoiceNinja extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-adjustments-vertical';
    protected static string|null|\UnitEnum $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Invoice Ninja Setup';
    public ?array $data = [];
    protected string $view = 'filament.pages.manage-invoice-ninja';

    public function mount(): void
    {
        // Wir laden die bestehenden Keys aus der DB
        $settings = InvoiceNinjaConfig::all()->pluck('value', 'key')->toArray();

        // Falls api_secret_token nicht in der DB, hole aus Konfiguration
        if (!isset($settings['api_secret_token']) || empty($settings['api_secret_token'])) {
            $settings['api_secret_token'] = config('services.invoice_ninja.api_secret_token');
        }

        $this->form->fill($settings);
    }

    public function form($schema)
    {
        return $schema
            ->schema([
                Section::make(__('Connection Settings'))
                    ->description(__('Needed for the API connection to your Invoice Ninja instance'))
                    ->schema([
                        TextInput::make('api_url')
                            ->label(__('Invoice Ninja URL'))
                            ->placeholder('https://invoices.co')
                            ->required()
                            ->url(),
                        TextInput::make('api_token')
                            ->label(__('API Token'))
                            ->password()
                            ->revealable()
                            ->required(),
                        TextInput::make('api_secret_token')
                            ->label(__('API Secret Token'))
                            ->password()
                            ->revealable()
                            ->placeholder(__('Optional for additional security layer')),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $state = $this->form->getState();

            foreach ($state as $key => $value) {
                InvoiceNinjaConfig::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            Notification::make()
                ->title(__('Settings saved!'))
                ->success()
                ->send();

        } catch (Halt $exception) {
            Notification::make()
                ->title(__('Failed to save settings'))
                ->body(__('An error occurred while saving the settings.'))
                ->danger()
                ->send();
            return;
        }
    }

    protected function getActions(): array
    {
        return $this->getFormActions();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save Configuration'))
                ->submit('save'),
            Action::make('testConnection')
                ->label('Verbindung testen')
                ->color('gray')
                ->action(function (InvoiceNinjaService $service) {
                    $data = $this->form->getState();
                    $result = $service->testConnection($data);

                    $notification = Notification::make()
                        ->title($result['message']);

                    if ($result['success']) {
                        $notification->success()->send();
                    } else {
                        $notification->danger()->persistent()->send();
                    }
                }),
        ];
    }
}
