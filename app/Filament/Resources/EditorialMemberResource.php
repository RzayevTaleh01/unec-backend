<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditorialMemberResource\Pages;
use App\Models\EditorialMember;
use App\Filament\Concerns\AdminOnly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EditorialMemberResource extends Resource
{
    use AdminOnly;
    use Translatable;

    protected static ?string $model = EditorialMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?string $navigationLabel = 'Redaksiya heyəti';

    protected static ?string $modelLabel = 'üzv';

    protected static ?string $pluralModelLabel = 'Redaksiya heyəti';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->label('Ad Soyad')->required()->maxLength(255),
                Forms\Components\TextInput::make('role')->label('Vəzifə')->required()->maxLength(255),
                Forms\Components\FileUpload::make('photo')->label('Şəkil')->image()->imageEditor()->avatar()
                    ->directory('editorial')->maxSize(2048),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Saytda göstər')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')->label('')->circular()
                    ->getStateUsing(fn (EditorialMember $record) => $record->photo_url),
                Tables\Columns\TextColumn::make('name')->label('Ad Soyad')->searchable(),
                Tables\Columns\TextColumn::make('role')->label('Vəzifə'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Saytda'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditorialMembers::route('/'),
            'create' => Pages\CreateEditorialMember::route('/create'),
            'edit' => Pages\EditEditorialMember::route('/{record}/edit'),
        ];
    }
}
