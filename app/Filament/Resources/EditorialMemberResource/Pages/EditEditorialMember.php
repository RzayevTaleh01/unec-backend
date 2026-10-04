<?php

namespace App\Filament\Resources\EditorialMemberResource\Pages;

use App\Filament\Resources\EditorialMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEditorialMember extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = EditorialMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
