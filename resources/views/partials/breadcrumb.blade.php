{{-- $crumbs: [label => url|null]; last item is the current page. --}}
<section class="archive-breadcrumb">
    <div class="container {{ $containerClass ?? 'archive-container' }}">
        <nav class="archive-breadcrumb-nav">
            <a href="{{ route('home') }}">{{ __('site.nav.home') }}</a>
            @foreach ($crumbs as $label => $url)
                <span>/</span>
                @if ($url)
                    <a href="{{ $url }}">{{ $label }}</a>
                @else
                    <span class="current">{{ $label }}</span>
                @endif
            @endforeach
        </nav>
    </div>
</section>
