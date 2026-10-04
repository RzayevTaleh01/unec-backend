<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Rəyçilər və rəylər';

    public function form(Form $form): Form
    {
        // Used by the read-only "view" modal.
        return $form->schema([
            Forms\Components\TextInput::make('recommendation')->label('Tövsiyə')
                ->formatStateUsing(fn (?string $state) => $state ? __('site.recommendations.'.$state) : '—'),
            Forms\Components\Textarea::make('comments_to_author')->label('Müəllif üçün qeydlər')->rows(6),
            Forms\Components\Textarea::make('comments_to_editor')->label('Redaktor üçün qeydlər')->rows(4),
        ])->disabled();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('reviewer.name')->label('Rəyçi'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => __('site.reviews.statuses.'.$state))
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success', 'declined' => 'danger', 'accepted' => 'info', default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('recommendation')->label('Tövsiyə')->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state ? __('site.recommendations.'.$state) : null),
                Tables\Columns\TextColumn::make('due_at')->label('Son tarix')->date('d.m.Y')->placeholder('—'),
                Tables\Columns\TextColumn::make('completed_at')->label('Tamamlanıb')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Rəyə bax')
                    ->visible(fn ($record) => $record->status === 'completed'),
                Tables\Actions\DeleteAction::make()->label('Ləğv et')
                    ->visible(fn ($record) => $record->status === 'pending'),
            ]);
    }
}
