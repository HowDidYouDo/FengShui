<?php

namespace App\Filament\Resources\Features\Pages;

use App\Filament\Resources\Features\FeatureResource;
use App\Services\InvoiceNinjaService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListFeatures extends ListRecords
{
    use Translatable;

    protected static string $resource = FeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),

            // Der neue Synchronisations-Button
            Action::make('syncFromNinja')
                ->label(__('Sync from Invoice Ninja'))
                ->color('info')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading(__('Synchronize Products'))
                ->modalDescription(__('Only products with "FS_R" or "FS_O" in Custom Field 1 will be imported.'))
                ->action(function (InvoiceNinjaService $service) {
                    $results = $service->syncFeaturesFromNinja();

                    Notification::make()
                        ->title(__('Synchronization complete'))
                        ->body(__("Created: :created | Updated: :updated", [
                            'created' => $results['created'],
                            'updated' => $results['updated'],
                        ]))
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
