<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // is_admin is deliberately not mass-assignable; set it explicitly here.
        $isAdmin = (bool) ($data['is_admin'] ?? false);
        unset($data['is_admin']);

        // Never let an admin lock themselves out of the panel.
        if ($record->is(auth()->user())) {
            $isAdmin = true;
        }

        $record->update($data);
        $record->forceFill(['is_admin' => $isAdmin])->save();

        return $record;
    }
}
