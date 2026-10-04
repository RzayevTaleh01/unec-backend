<?php

namespace App\Filament\Resources\EditorialMemberResource\Pages;

use App\Filament\Resources\EditorialMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEditorialMembers extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = EditorialMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
