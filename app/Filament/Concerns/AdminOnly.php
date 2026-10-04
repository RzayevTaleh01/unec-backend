<?php

namespace App\Filament\Concerns;

/** For resources that only System Administrators may open (content, users, settings). */
trait AdminOnly
{
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canEdit($record): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canDelete($record): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canDeleteAny(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }
}
