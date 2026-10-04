<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IssueResource\Pages;
use App\Filament\Resources\IssueResource\RelationManagers\ArticlesRelationManager;
use App\Models\Issue;
use App\Filament\Concerns\AdminOnly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IssueResource extends Resource
{
    use AdminOnly;
    use Translatable;

    protected static ?string $model = Issue::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?string $navigationLabel = 'Buraxılışlar (Arxiv)';

    protected static ?string $modelLabel = 'buraxılış';

    protected static ?string $pluralModelLabel = 'Buraxılışlar';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Nömrə')->schema([
                Forms\Components\TextInput::make('volume')->label('Cild')->numeric()->minValue(1)->required(),
                Forms\Components\TextInput::make('number')->label('Nömrə')->numeric()->minValue(1)->required(),
                Forms\Components\TextInput::make('year')->label('İl')->numeric()->minValue(2000)->maxValue(2100)->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule, Forms\Get $get) => $rule->where('volume', $get('volume'))->where('number', $get('number')),
                    ),
                Forms\Components\DatePicker::make('published_at')->label('Dərc tarixi')->native(false)->displayFormat('d.m.Y'),
                Forms\Components\Toggle::make('is_published')->label('Saytda göstər')->default(true),
            ])->columns(3),

            Forms\Components\Section::make('Təsvir')->schema([
                Forms\Components\Textarea::make('title')->label('Başlıq (könüllü)')->rows(2)->maxLength(500)
                    ->helperText('Boş buraxılsa, "Cild. 3 Nömrə. 1 (2026)" formatı istifadə olunur.'),
                Forms\Components\Textarea::make('description')->label('Təsvir')->rows(8)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Fayllar')->schema([
                Forms\Components\FileUpload::make('cover')
                    ->label('Üz qabığı')
                    ->image()
                    ->imageEditor()
                    ->directory('issues/covers')
                    ->maxSize(4096),
                Forms\Components\FileUpload::make('pdf')
                    ->label('PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->directory('issues/pdf')
                    ->maxSize(51200),
                Forms\Components\TextInput::make('doi')->label('DOI')->maxLength(255)
                    ->helperText('Məsələn: 10.30546/0a2ted920 və ya tam link'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')->label('')->height(48)
                    ->getStateUsing(fn (Issue $record) => $record->cover_url),
                Tables\Columns\TextColumn::make('label')->label('Buraxılış')
                    ->state(fn (Issue $record) => $record->label),
                Tables\Columns\TextColumn::make('year')->label('İl')->sortable(),
                Tables\Columns\TextColumn::make('articles_count')->counts('articles')->label('Məqalə'),
                Tables\Columns\TextColumn::make('published_at')->label('Dərc tarixi')->date('d.m.Y')->sortable(),
                Tables\Columns\ToggleColumn::make('is_published')->label('Saytda'),
            ])
            ->defaultSort('year', 'desc')
            ->actions([
                Tables\Actions\Action::make('open')->label('Saytda aç')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Issue $record) => route('archive.show', $record))->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [ArticlesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
        ];
    }
}
