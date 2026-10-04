<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use App\Models\ArticleFile;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Yüklənmiş fayllar';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                Tables\Columns\TextColumn::make('original_name')->label('Fayl'),
                Tables\Columns\TextColumn::make('type')->label('Növ')->badge()
                    ->formatStateUsing(fn (string $state) => __('site.wizard.file_types.'.$state)),
                Tables\Columns\TextColumn::make('size')->label('Ölçü')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024 / 1024, 2).' MB'),
                Tables\Columns\TextColumn::make('uploader.name')->label('Yükləyən'),
                Tables\Columns\TextColumn::make('created_at')->label('Tarix')->dateTime('d.m.Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')->label('Yüklə')->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (ArticleFile $record) => route('files.download', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
