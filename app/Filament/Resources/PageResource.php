<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use App\Models\PageSection;
use App\Filament\Concerns\AdminOnly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class PageResource extends Resource
{
    use AdminOnly;
    use Translatable;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Məzmun';

    protected static ?string $navigationLabel = 'Səhifələr';

    protected static ?string $modelLabel = 'səhifə';

    protected static ?string $pluralModelLabel = 'Səhifələr';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('page_section_id')
                    ->label('Bölmə')
                    ->options(fn () => PageSection::orderBy('sort_order')->get()->pluck('title', 'id'))
                    ->placeholder('Müstəqil səhifə (bölməsiz)')
                    ->helperText('Bölmənin menyusunda bu səhifə ayrıca tab kimi görünür.')
                    ->live(),
                Forms\Components\TextInput::make('title')
                    ->label('Başlıq')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('URL adı')
                    ->required()
                    ->alphaDash()
                    ->maxLength(100)
                    ->helperText('Məsələn: purpose. Bölməsiz səhifələr üçün yalnız: privacy, terms, open-journal-systems.')
                    ->rules(fn (Forms\Get $get, ?Page $record) => [
                        Rule::unique('pages', 'slug')
                            ->where(fn ($query) => $query->where('page_section_id', $get('page_section_id') ?: null))
                            ->ignore($record?->id),
                    ]),
                Forms\Components\RichEditor::make('body')
                    ->label('Məzmun')
                    ->columnSpanFull()
                    ->disableToolbarButtons(['attachFiles', 'codeBlock']),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sıra')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_published')
                    ->label('Dərc edilib')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section.title')
                    ->label('Bölmə')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('URL')->color('gray'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\ToggleColumn::make('is_published')->label('Dərc edilib'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('page_section_id')
                    ->label('Bölmə')
                    ->options(fn () => PageSection::orderBy('sort_order')->get()->pluck('title', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Saytda aç')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Page $record) => $record->section
                        ? route('pages.show', [$record->section->key, $record->slug])
                        : url('/'.$record->slug))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
