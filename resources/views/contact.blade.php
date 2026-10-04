@extends('layouts.app')

@section('title', __('site.nav.contact'))

@section('content')
    <main class="contact-section" id="contact">
        <div class="container contact-container">
            <h1 class="contact-heading">{{ __('site.nav.contact') }}</h1>

            <div class="contact-map" aria-label="{{ __('site.contact.map') }}">
                <div class="map-marker"><i class="bi bi-geo-alt-fill"></i></div>

                <div class="contact-cards">
                    @foreach ($contacts as $contact)
                        <article class="contact-card">
                            <h2>{{ $contact->title }}</h2>
                            <h3>{{ $contact->name }}</h3>
                            <p>{!! nl2br(e($contact->organization)) !!}</p>
                            <div class="contact-card-divider"></div>
                            @if ($contact->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', preg_replace('/\(.*\)/', '', $contact->phone)) }}" class="contact-detail">
                                    <i class="bi bi-telephone"></i><span>{{ $contact->phone }}</span>
                                </a>
                            @endif
                            @if ($contact->email)
                                <a href="mailto:{{ $contact->email }}" class="contact-detail">
                                    <i class="bi bi-envelope"></i><span>{{ $contact->email }}</span>
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </main>
@endsection
