<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Exceptions\WorkflowException;
use App\Filament\Resources\ArticleResource;
use App\Models\Issue;
use App\Models\User;
use App\Services\SubmissionWorkflow;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            $this->assignReviewerAction(),
            $this->decisionAction(),
            Actions\DeleteAction::make()->visible(fn () => (bool) auth()->user()?->is_admin),
        ];
    }

    private function assignReviewerAction(): Actions\Action
    {
        return Actions\Action::make('assignReviewer')
            ->label('Rəyçi təyin et')
            ->icon('heroicon-o-user-plus')
            ->visible(fn () => in_array($this->record->status, ['submitted', 'in_review'], true))
            ->form([
                Forms\Components\Select::make('reviewer_id')
                    ->label('Rəyçi')
                    ->options(fn () => User::whereJsonContains('roles', 'reviewer')
                        ->withCount(['reviews as open_reviews_count' => fn ($q) => $q->whereIn('status', ['pending', 'accepted'])])
                        ->orderBy('name')->get()
                        ->mapWithKeys(fn (User $user) => [$user->id => trim($user->name.($user->specialty ? ' ('.$user->specialty.')' : '').' · açıq rəy: '.$user->open_reviews_count)]))
                    ->searchable()
                    ->required()
                    ->helperText('Yalnız administrator tərəfindən "Rəyçi" rolu verilmiş istifadəçilər göstərilir. Məqalənin müəllifləri təyin edilə bilməz.'),
                Forms\Components\DatePicker::make('due_at')->label('Son tarix')->native(false)->minDate(today()),
            ])
            ->action(function (array $data) {
                try {
                    app(SubmissionWorkflow::class)->assignReviewer(
                        $this->record,
                        User::findOrFail($data['reviewer_id']),
                        auth()->user(),
                        filled($data['due_at'] ?? null) ? Carbon::parse($data['due_at']) : null,
                    );
                } catch (WorkflowException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Rəyçi təyin edildi və dəvət göndərildi')->success()->send();
                $this->redirect(ArticleResource::getUrl('edit', ['record' => $this->record]));
            });
    }

    private function decisionAction(): Actions\Action
    {
        $workflow = app(SubmissionWorkflow::class);
        $labels = collect(['accept', 'revisions', 'reject', 'publish'])->mapWithKeys(fn ($d) => [$d => __('site.decisions.'.$d)]);

        return Actions\Action::make('decide')
            ->label('Qərar ver')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn () => $workflow->allowedDecisions($this->record) !== [])
            ->form(fn () => [
                Forms\Components\Select::make('decision')
                    ->label('Qərar')
                    ->options(fn () => $labels->only($workflow->allowedDecisions($this->record))->all())
                    ->required()
                    ->live(),
                Forms\Components\Select::make('issue_id')
                    ->label('Buraxılış')
                    ->options(fn () => Issue::latestFirst()->get()->mapWithKeys(fn (Issue $issue) => [$issue->id => $issue->label]))
                    ->default($this->record->issue_id)
                    ->visible(fn (Forms\Get $get) => $get('decision') === 'publish')
                    ->required(fn (Forms\Get $get) => $get('decision') === 'publish'),
                Forms\Components\Textarea::make('comment')
                    ->label('Müəllifə qeyd')
                    ->rows(5)
                    ->helperText('Müəllif bu qeydi təqdimat səhifəsində görəcək.'),
            ])
            ->action(function (array $data) use ($workflow) {
                try {
                    $workflow->decide(
                        $this->record,
                        auth()->user(),
                        $data['decision'],
                        $data['comment'] ?? null,
                        filled($data['issue_id'] ?? null) ? Issue::find($data['issue_id']) : null,
                    );
                } catch (WorkflowException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Qərar qeyd edildi, müəllifə bildiriş göndərildi')->success()->send();
                $this->redirect(ArticleResource::getUrl('edit', ['record' => $this->record]));
            });
    }
}
