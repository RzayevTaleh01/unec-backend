<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use App\Models\Article;
use App\Models\Issue;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Buraxılışlar', Issue::count())->description(Issue::published()->count().' dərc edilib')->icon('heroicon-o-book-open'),
            Stat::make('Məqalələr', Article::count())->description(Article::published()->count().' dərc edilib')->icon('heroicon-o-document-duplicate'),
            Stat::make('Elanlar', Announcement::count())->description(Announcement::published()->count().' saytda görünür')->icon('heroicon-o-megaphone'),
            Stat::make('İstifadəçilər', User::count())->icon('heroicon-o-users'),
        ];
    }
}
