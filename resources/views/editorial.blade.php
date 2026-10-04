@extends('layouts.app')

@section('title', __('site.nav.editorial'))

@section('content')
    @include('partials.breadcrumb', ['crumbs' => [__('site.nav.editorial') => null], 'containerClass' => ''])

    <main class="editorial-section">
        <section class="team-section">
            <div class="container">
                <div class="row g-3">
                    @forelse ($members as $member)
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="team-card">
                                <div class="team-card-image"><img src="{{ $member->photo_url ?? asset('assets/images/announcements/1.png') }}" alt="{{ $member->name }}"></div>
                                <div class="team-card-body">
                                    <h3 class="team-card-name">{{ $member->name }}</h3>
                                    <p class="team-card-role">{{ $member->role }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p>{{ __('site.editorial.empty') }}</p>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
@endsection
