<?php

namespace App\Filament\Resources\IssueResource\RelationManagers;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';

    protected static ?string $title = 'Bu buraxılışın məqalələri';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->limit(70)->wrap(),
                Tables\Columns\TextColumn::make('authors.name')->label('Müəlliflər')->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => __('site.statuses.'.$state)),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('Yeni məqalə')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => ArticleResource::getUrl('create', ['issue' => $this->getOwnerRecord()->getKey()])),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')->label('Redaktə')->icon('heroicon-o-pencil-square')
                    ->url(fn (Article $record) => ArticleResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
