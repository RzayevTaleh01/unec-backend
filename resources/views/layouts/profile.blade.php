<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('site.profile')) | {{ __('site.journal_name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>

<body class="profile-page">
    <header class="profile-header">
        <div class="profile-header-actions">
            @php($unread = auth()->user()->unreadNotifications()->count())
            <a class="profile-bell" href="{{ route('profile.inbox') }}" aria-label="{{ __('site.inbox.title') }}">
                <i class="bi bi-bell"></i>
                @if ($unread)<b class="profile-bell-count">{{ $unread > 9 ? '9+' : $unread }}</b>@endif
            </a>
            <span></span>
            <a href="{{ route('profile.identity') }}" aria-label="{{ __('site.profile') }}"><i class="bi bi-person"></i></a>
        </div>
    </header>

    <div class="profile-layout">
        <aside class="profile-sidebar" data-profile-sidebar>
            <div class="profile-sidebar-brand">
                <a class="profile-brand" href="{{ route('home') }}">{!! nl2br(e(__('site.footer.brand'))) !!}</a>
                <button class="profile-sidebar-toggle" type="button" aria-label="{{ __('site.profile_page.close_menu') }}" data-profile-menu-toggle>
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>
            <nav aria-label="{{ __('site.profile_page.menu') }}">
                <a class="{{ request()->routeIs('profile.identity', 'profile.contact*', 'profile.roles*', 'profile.public*', 'profile.password*', 'profile.notifications*', 'profile.api-key*') ? 'active' : '' }}" href="{{ route('profile.identity') }}"><i class="bi bi-person"></i><span>{{ __('site.profile') }}</span></a>
                <a class="{{ request()->routeIs('submit.*') ? 'active' : '' }}" href="{{ route('submit.start') }}"><i class="bi bi-plus-circle"></i><span>{{ __('site.wizard.new_submission') }}</span></a>
                <a class="{{ request()->routeIs('profile.submissions*') ? 'active' : '' }}" href="{{ route('profile.submissions') }}"><i class="bi bi-file-earmark-text"></i><span>{{ __('site.profile_page.submissions') }}</span></a>
                @if (auth()->user()->hasRole('reviewer'))
                    <a class="{{ request()->routeIs('profile.reviews*') ? 'active' : '' }}" href="{{ route('profile.reviews') }}"><i class="bi bi-clipboard-check"></i><span>{{ __('site.profile_page.reviews') }}</span></a>
                @endif
                @if (auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                    <a href="{{ url('/admin') }}"><i class="bi bi-gear"></i><span>{{ __('site.profile_page.admin') }}</span></a>
                @endif
            </nav>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="profile-logout" type="submit"><i class="bi bi-box-arrow-right"></i>{{ __('site.profile_page.logout') }}</button>
            </form>
        </aside>

        <main class="profile-main">
            @hasSection('tabs')
                @include('profile._tabs')
            @endif

            @if (session('status'))
                <div class="auth-alert auth-alert--success">{{ session('status') }}</div>
            @endif

            @yield('panel')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    @stack('scripts')
</body>

</html>
