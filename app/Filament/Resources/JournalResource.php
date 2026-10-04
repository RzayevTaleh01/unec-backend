<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Models\Journal;
use App\Filament\Concerns\AdminOnly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JournalResource extends Resource
{
    use AdminOnly;
    use Translatable;

    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?string $navigationLabel = 'Digər jurnallar';

    protected static ?string $modelLabel = 'jurnal';

    protected static ?string $pluralModelLabel = 'Jurnallar';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->label('Jurnalın adı')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->label('URL adı')->required()->alphaDash()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('url')->label('Sayt linki')->url()->maxLength(255),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_primary')->label('Əsas jurnal')
                    ->helperText('Profildəki "Digər jurnallarda qeydiyyat" siyahısında əsas jurnal göstərilmir.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Jurnal')->searchable()->wrap(),
                Tables\Columns\IconColumn::make('is_primary')->label('Əsas')->boolean(),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Üzv sayı'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}
