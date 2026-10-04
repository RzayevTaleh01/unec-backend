<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'İstifadəçilər';

    protected static ?string $navigationLabel = 'İstifadəçilər';

    protected static ?string $modelLabel = 'istifadəçi';

    protected static ?string $pluralModelLabel = 'İstifadəçilər';

    /** Accounts are created through public registration or `php artisan app:create-admin`. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Şəxsi məlumat')->schema([
                Forms\Components\TextInput::make('first_name')->label('Ad')->required()->maxLength(100),
                Forms\Components\TextInput::make('last_name')->label('Soyad')->maxLength(100),
                Forms\Components\TextInput::make('username')->label('İstifadəçi adı')->required()->alphaDash()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->label('Telefon')->tel(),
                Forms\Components\TextInput::make('institution')->label('Qurum'),
                Forms\Components\Select::make('country')->label('Ölkə')
                    ->options(['az' => 'Azərbaycan', 'tr' => 'Türkiyə', 'other' => 'Digər']),
            ])->columns(2),

            Forms\Components\Section::make('Rollar və giriş')->schema([
                Forms\Components\CheckboxList::make('roles')->label('Rollar')
                    ->options(collect(User::ROLES)->mapWithKeys(fn ($role) => [$role => __('site.roles.'.$role)]))
                    ->columns(2)
                    ->helperText('Oxucu, müəllif və rəyçi rolunu istifadəçi özü də seçə bilər. Baş redaktor, bölmə redaktoru, kopirayter və mətbəəçi rolları yalnız burada verilir. Baş redaktor və bölmə redaktoru admin panelə (Məqalələr bölməsinə) giriş əldə edir.'),
                Forms\Components\Toggle::make('is_admin')->label('Sistem administratoru')
                    ->helperText('Tam giriş: istifadəçilər, məzmun, ayarlar. Yalnız etibarlı şəxslərə verin.'),
                Forms\Components\Placeholder::make('reviewer_interest')->label('Rəyçi olmağa razılıq')
                    ->content(fn (?User $record) => $record?->consent_reviewer_contact ? 'Bəli, istifadəçi rəyçi dəvətinə razıdır' : 'Bildirilməyib'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad Soyad')->searchable(),
                Tables\Columns\TextColumn::make('username')->label('İstifadəçi adı')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('roles')->label('Rollar')->badge()
                    ->formatStateUsing(fn (string $state) => __('site.roles.'.$state)),
                Tables\Columns\IconColumn::make('consent_reviewer_contact')->label('Rəyçi olmağa razı')->boolean()
                    ->trueIcon('heroicon-o-hand-raised')->falseIcon('heroicon-o-minus')->falseColor('gray'),
                Tables\Columns\IconColumn::make('is_admin')->label('Admin')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Qeydiyyat')->date('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_admin')->label('Admin'),
                Tables\Filters\TernaryFilter::make('consent_reviewer_contact')->label('Rəyçi olmağa razı olanlar'),
                Tables\Filters\SelectFilter::make('role')->label('Rol')
                    ->options(collect(User::ROLES)->mapWithKeys(fn ($role) => [$role => __('site.roles.'.$role)]))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null) ? $query->whereJsonContains('roles', $data['value']) : $query),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
