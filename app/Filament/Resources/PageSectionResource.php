<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageSectionResource\Pages;
use App\Models\PageSection;
use App\Filament\Concerns\AdminOnly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class PageSectionResource extends Resource
{
    use AdminOnly;
    use Translatable;

    /** URL prefixes the application already uses; a section key must not shadow them. */
    public const RESERVED_KEYS = [
        'admin', 'api', 'archive', 'articles', 'announcements', 'search', 'editorial-board', 'contact', 'lang',
        'login', 'logout', 'register', 'forgot-password', 'reset-password', 'profile', 'privacy', 'terms',
        'open-journal-systems', 'storage', 'livewire', 'filament', 'up', 'assets',
    ];

    protected static ?string $model = PageSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Məzmun';

    protected static ?string $navigationLabel = 'Səhifə bölmələri';

    protected static ?string $modelLabel = 'bölmə';

    protected static ?string $pluralModelLabel = 'Səhifə bölmələri';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('title')->label('Bölmənin adı')->required()->maxLength(255),
                Forms\Components\TextInput::make('key')
                    ->label('URL açarı')
                    ->required()
                    ->alphaDash()
                    ->maxLength(50)
                    ->notIn(self::RESERVED_KEYS)
                    ->unique(ignoreRecord: true)
                    ->helperText('Bölmə /açar/səhifə-adı ünvanında açılır. Dəyişdirsəniz, köhnə linklər işləməyəcək.'),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Bölmə')->searchable(),
                Tables\Columns\TextColumn::make('key')->label('URL açarı')->color('gray'),
                Tables\Columns\TextColumn::make('pages_count')->counts('pages')->label('Səhifə sayı'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPageSections::route('/'),
            'create' => Pages\CreatePageSection::route('/create'),
            'edit' => Pages\EditPageSection::route('/{record}/edit'),
        ];
    }
}
