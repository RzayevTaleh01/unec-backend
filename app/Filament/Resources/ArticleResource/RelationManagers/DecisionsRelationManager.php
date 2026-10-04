<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DecisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'decisions';

    protected static ?string $title = 'Qərarlar tarixçəsi';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('decision')
            ->columns([
                Tables\Columns\TextColumn::make('decision')->label('Qərar')->badge()
                    ->formatStateUsing(fn (string $state) => __('site.decisions.'.$state)),
                Tables\Columns\TextColumn::make('editor.name')->label('Redaktor'),
                Tables\Columns\TextColumn::make('comment')->label('Qeyd')->wrap()->limit(120),
                Tables\Columns\TextColumn::make('created_at')->label('Tarix')->dateTime('d.m.Y H:i'),
            ]);
    }
}
