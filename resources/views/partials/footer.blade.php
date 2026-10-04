@php
    $footerQuote = \App\Models\Setting::get('footer_quote', __('site.footer.quote'));
    $footerAuthor = \App\Models\Setting::get('footer_quote_author', __('site.footer.quote_author'));
@endphp
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="footer-brand">{!! nl2br(e(__('site.footer.brand'))) !!}</div>
                <div class="footer-quote">“{{ $footerQuote }}”<br><i>{{ $footerAuthor }}</i></div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-heading">{{ __('site.footer.quick_links') }}</div>
                <a class="footer-link" href="{{ route('home') }}">{{ __('site.nav.home') }}</a>
                <a class="footer-link" href="{{ route('archive.index') }}">{{ __('site.nav.archive') }}</a>
                <a class="footer-link" href="{{ route('announcements.index') }}">{{ __('site.nav.announcements') }}</a>
                <a class="footer-link" href="{{ route('contact') }}">{{ __('site.nav.contact') }}</a>
                <a class="footer-link" href="{{ route('systems') }}">{{ __('site.nav.systems') }}</a>
            </div>
            <div class="col-6 col-lg-3">
                <div class="footer-heading">{{ __('site.footer.about') }}</div>
                <a class="footer-link" href="{{ route('pages.first', 'about') }}">{{ __('site.nav.about_journal') }}</a>
                <a class="footer-link" href="{{ route('pages.first', 'submission') }}">{{ __('site.nav.article_submission') }}</a>
                <a class="footer-link" href="{{ route('editorial') }}">{{ __('site.nav.editorial') }}</a>
                <a class="footer-link" href="{{ route('pages.first', 'information') }}">{{ __('site.nav.information') }}</a>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>© {{ date('Y') }} {{ __('site.footer.university') }} {{ __('site.footer.rights') }}</div>
            <div><a href="{{ route('privacy') }}">{{ __('site.footer.privacy') }}</a><span class="footer-separator">|</span><a href="{{ route('terms') }}">{{ __('site.footer.terms') }}</a></div>
        </div>
    </div>
</footer>
