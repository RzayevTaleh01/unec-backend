<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Models\Issue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticleResource extends Resource
{
    use Translatable;

    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?string $navigationLabel = 'Məqalələr';

    protected static ?string $modelLabel = 'məqalə';

    protected static ?string $pluralModelLabel = 'Məqalələr';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Məqalə')->schema([
                Forms\Components\Textarea::make('title')->label('Başlıq')->required()->rows(2)->maxLength(500)->columnSpanFull(),
                Forms\Components\Textarea::make('abstract')->label('Xülasə')->rows(8)->columnSpanFull(),
                Forms\Components\TextInput::make('keywords')->label('Açar sözlər')->helperText('Vergüllə ayırın')->maxLength(500)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Müəlliflər')->schema([
                Forms\Components\Repeater::make('authors')
                    ->relationship()
                    ->label('')
                    ->orderColumn('sort_order')
                    ->reorderable()
                    ->addActionLabel('Müəllif əlavə et')
                    ->itemLabel(fn (array $state) => $state['name'] ?? null)
                    ->minItems(1)
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Ad Soyad')->required()->maxLength(255),
                        Forms\Components\TextInput::make('institution')->label('Qurum')->maxLength(255),
                        Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255),
                        Forms\Components\Toggle::make('is_primary')->label('Əsas müəllif'),
                    ])
                    ->columns(2),
            ]),

            Forms\Components\Section::make('Nəşr məlumatı')->schema([
                Forms\Components\Select::make('issue_id')
                    ->label('Buraxılış')
                    ->options(fn () => Issue::latestFirst()->get()->mapWithKeys(fn (Issue $issue) => [$issue->id => $issue->label]))
                    ->default(fn () => request()->integer('issue') ?: null)
                    ->searchable(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(collect(Article::STATUSES)->mapWithKeys(fn ($status) => [$status => __('site.statuses.'.$status)]))
                    ->default('published')
                    ->required()
                    // On existing articles the status only changes through the workflow actions.
                    ->disabled(fn (string $operation) => $operation === 'edit')
                    ->dehydrated(fn (string $operation) => $operation === 'create')
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Status yuxarıdakı "Rəyçi təyin et" / "Qərar ver" düymələri ilə dəyişir.' : null),
                Forms\Components\Select::make('language')->label('Dil')
                    ->options(['az' => 'Azərbaycan', 'en' => 'English', 'ru' => 'Русский', 'tr' => 'Türkçe'])
                    ->default('az')->required(),
                Forms\Components\TextInput::make('pages')->label('Səhifələr')->maxLength(50),
                Forms\Components\DatePicker::make('published_at')->label('Dərc tarixi')->native(false)->displayFormat('d.m.Y'),
                Forms\Components\TextInput::make('doi')->label('DOI')->maxLength(255),
                Forms\Components\FileUpload::make('pdf')->label('PDF')->acceptedFileTypes(['application/pdf'])
                    ->directory('articles/pdf')->maxSize(51200),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->limit(70)->wrap()->searchable()
                    ->extraHeaderAttributes(['style' => 'min-width: 280px']),
                Tables\Columns\TextColumn::make('authors.name')->label('Müəlliflər')->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('issue')->label('Buraxılış')->state(fn (Article $record) => $record->issue?->label),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => __('site.statuses.'.$state))
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success', 'rejected' => 'danger', 'draft' => 'gray', default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('published_at')->label('Dərc tarixi')->date('d.m.Y')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')
                    ->options(collect(Article::STATUSES)->mapWithKeys(fn ($status) => [$status => __('site.statuses.'.$status)])),
                Tables\Filters\SelectFilter::make('issue_id')->label('Buraxılış')
                    ->options(fn () => Issue::latestFirst()->get()->mapWithKeys(fn (Issue $issue) => [$issue->id => $issue->label])),
            ])
            ->actions([
                Tables\Actions\Action::make('open')->label('Saytda aç')->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (Article $record) => $record->status === 'published')
                    ->url(fn (Article $record) => route('articles.show', $record))->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            ArticleResource\RelationManagers\FilesRelationManager::class,
            ArticleResource\RelationManagers\ReviewsRelationManager::class,
            ArticleResource\RelationManagers\DecisionsRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->is_admin || $user?->isEditor());
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canDeleteAny(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    /** Number of new submissions waiting for the editors. */
    public static function getNavigationBadge(): ?string
    {
        $count = Article::where('status', 'submitted')->count();

        return $count ?: null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('authors', 'issue');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
