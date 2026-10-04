<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Filament\Concerns\AdminOnly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    use AdminOnly;
    use Translatable;

    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Məzmun';

    protected static ?string $navigationLabel = 'Elanlar';

    protected static ?string $modelLabel = 'elan';

    protected static ?string $pluralModelLabel = 'Elanlar';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Textarea::make('title')->label('Başlıq')->required()->rows(2)->maxLength(500)->columnSpanFull(),
                Forms\Components\RichEditor::make('body')->label('Mətn')->columnSpanFull()
                    ->disableToolbarButtons(['attachFiles', 'codeBlock']),
                Forms\Components\FileUpload::make('image')->label('Şəkil')->image()->imageEditor()
                    ->directory('announcements')->maxSize(4096),
                Forms\Components\Group::make([
                    Forms\Components\DatePicker::make('published_at')->label('Dərc tarixi')->native(false)
                        ->displayFormat('d.m.Y')->default(now())->required()
                        ->helperText('Gələcək tarix seçsəniz, elan həmin gün saytda görünəcək.'),
                    Forms\Components\Toggle::make('is_published')->label('Dərc edilib')->default(true),
                    Forms\Components\TextInput::make('views_count')->label('Baxış sayı')->disabled()->dehydrated(false)->visibleOn('edit'),
                ]),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->height(44)
                    ->getStateUsing(fn (Announcement $record) => $record->image_url),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->limit(70)->wrap()->searchable(),
                Tables\Columns\TextColumn::make('published_at')->label('Tarix')->date('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('views_count')->label('Baxış')->sortable(),
                Tables\Columns\ToggleColumn::make('is_published')->label('Dərc edilib'),
            ])
            ->defaultSort('published_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('open')->label('Saytda aç')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Announcement $record) => route('announcements.show', $record))->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
