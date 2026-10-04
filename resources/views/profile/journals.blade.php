@extends('layouts.profile')

@section('title', __('site.profile_page.other_journals'))
@section('tabs', true)

@section('panel')
    <section class="profile-panel">
        <a class="back-link" href="{{ route('profile.roles') }}"><i class="bi bi-arrow-left"></i> {{ __('site.back') }}</a>
        <h2 class="profile-notification-heading">{{ __('site.profile_page.other_journals') }}</h2>
        <form class="profile-form" method="post" action="{{ route('profile.roles.journals.update') }}">
            @csrf
            @method('put')
            @forelse ($journals as $journal)
                @php $current = $memberships[$journal->id] ?? []; @endphp
                <div class="profile-journal">
                    <strong>{{ $journal->name }}</strong>
                    @foreach (\App\Models\User::SELF_ROLES as $role)
                        <label><input type="checkbox" name="journals[{{ $journal->id }}][]" value="{{ $role }}" @checked(in_array($role, $current, true))> {{ __('site.roles.'.$role) }}</label>
                    @endforeach
                </div>
            @empty
                <p>{{ __('site.profile_page.no_journals') }}</p>
            @endforelse
            @include('profile._footer')
        </form>
    </section>
@endsection
