@php
    $locales = ['az' => 'Azerbaijan', 'en' => 'English', 'ru' => 'Russian'];
    $systemsUrl = \App\Models\Setting::get('systems_url');
    // Sections added from the admin panel appear after the built-in "About" entries.
    $extraSections = \App\Models\PageSection::whereNotIn('key', ['about', 'submission', 'information'])->has('pages')->orderBy('sort_order')->get();
@endphp
<header class="site-header">
    <div class="topbar">
        <div class="container">
            <div class="header-left">
                <button class="icon-btn mobile-menu-btn" id="mobileMenuBtn" aria-label="{{ __('site.menu') }}">
                    <i class="bi bi-list"></i>
                </button>
                <a href="{{ route('search') }}" class="icon-btn search-btn" aria-label="{{ __('site.search') }}">
                    <i class="bi bi-search"></i>
                </a>
            </div>

            <div class="header-center">
                <a href="{{ route('home') }}" class="title-link">
                    <h1 class="title">{{ __('site.journal_name_upper') }}</h1>
                </a>
            </div>

            <div class="header-right">
                <div class="dropdown" id="langDropdown">
                    <button class="dropdown-btn" onclick="toggleDropdown()" type="button">
                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        @foreach ($locales as $code => $name)
                            <a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}"
                                href="{{ route('lang', $code) }}">
                                <div class="dropdown-item-left"><span>{{ $name }}</span></div>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="divider"></div>
                <a href="{{ auth()->check() ? route('profile.identity') : route('login') }}" class="icon-btn"
                    aria-label="{{ __('site.profile') }}">
                    <i class="bi bi-person"></i>
                </a>
            </div>
        </div>
    </div>

    <nav class="nav-row d-flex text-center align-items-center">
        <button class="mobile-close-btn" id="mobileCloseBtn" aria-label="{{ __('site.close') }}">
            <i class="bi bi-x"></i>
        </button>
        <div class="container">
            <a class="nav-link-custom" href="{{ route('home') }}">{{ __('site.nav.home') }}</a>

            <div class="dropdown" id="aboutDropdown">
                <button class="nav-link-custom dropdown-btn about-btn" onclick="toggleAboutDropdown()" type="button">
                    <span>{{ __('site.nav.about') }}</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="dropdown-menu about-menu">
                    <div class="about-dropdown-item"><a href="{{ route('pages.first', 'about') }}">{{ __('site.nav.about_journal') }}</a></div>
                    <div class="about-dropdown-item"><a href="{{ route('pages.first', 'submission') }}">{{ __('site.nav.article_submission') }}</a></div>
                    <div class="about-dropdown-item"><a href="{{ route('editorial') }}">{{ __('site.nav.editorial') }}</a></div>
                    <div class="about-dropdown-item"><a href="{{ route('pages.first', 'information') }}">{{ __('site.nav.information') }}</a></div>
                    @foreach ($extraSections as $extra)
                        <div class="about-dropdown-item"><a href="{{ route('pages.first', $extra->key) }}">{{ $extra->title }}</a></div>
                    @endforeach
                </div>
            </div>

            <a class="nav-link-custom" href="{{ route('archive.index') }}">{{ __('site.nav.archive') }}</a>
            <a class="nav-link-custom" href="{{ route('announcements.index') }}">{{ __('site.nav.announcements') }}</a>
            <a class="nav-link-custom" href="{{ route('contact') }}">{{ __('site.nav.contact') }}</a>
            <a class="nav-link-custom" href="{{ $systemsUrl ?: route('systems') }}" @if ($systemsUrl) target="_blank" rel="noopener" @endif>{{ __('site.nav.systems') }}
                <i class="bi bi-box-arrow-up-right"></i></a>
        </div>
    </nav>
</header>
