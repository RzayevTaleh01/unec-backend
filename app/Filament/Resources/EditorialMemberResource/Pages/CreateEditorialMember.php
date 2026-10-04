<?php

namespace App\Filament\Resources\EditorialMemberResource\Pages;

use App\Filament\Resources\EditorialMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEditorialMember extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = EditorialMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}
