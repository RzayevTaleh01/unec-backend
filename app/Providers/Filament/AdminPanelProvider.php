<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Filament\SpatieLaravelTranslatablePlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // No separate admin login: the public /login is the single entrance. Guests opening /admin are
            // sent there (Laravel's guest redirect) and come back to the panel after signing in.
            ->brandName('UNEC Jurnal · Admin')
            ->darkMode(false)
            ->sidebarWidth('14.5rem')
            ->maxContentWidth('full')
            ->font('Poppins')
            ->colors([
                'primary' => Color::hex('#2d6da8'),
                'gray' => Color::Slate,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => new HtmlString('<link rel="stylesheet" href="'.asset('assets/css/admin.css').'">'))
            ->navigationItems([
                NavigationItem::make('Sayta qayıt')->icon('heroicon-o-arrow-uturn-left')->url(fn () => route('home'))->sort(1000),
            ])
            ->userMenuItems([
                MenuItem::make()->label('Sayta qayıt')->icon('heroicon-o-arrow-uturn-left')->url(fn () => route('home'))->sort(-1),
                MenuItem::make()->label('Profilim')->icon('heroicon-o-user')->url(fn () => route('profile.identity')),
            ])
            ->navigationGroups(['Məzmun', 'Jurnal', 'İstifadəçilər', 'Ayarlar'])
            ->plugin(SpatieLaravelTranslatablePlugin::make()->defaultLocales(['az', 'en', 'ru']))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
