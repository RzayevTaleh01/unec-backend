@extends('layouts.profile')

@section('title', __('site.profile_page.tabs.public'))
@section('tabs', true)

@section('panel')
    <section class="profile-panel">
        <form class="profile-form" method="post" action="{{ route('profile.public.update') }}" enctype="multipart/form-data" data-editor-form>
            @csrf
            @method('put')
            <label for="profile-photo">{{ __('site.profile_page.photo') }}</label>
            @if ($user->photo_url)
                <img src="{{ $user->photo_url }}" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin-bottom:10px">
            @endif
            @include('partials.file-input', [
                'id' => 'profile-photo', 'name' => 'photo', 'accept' => '.jpg,.jpeg,.png,.webp',
                'maxMb' => 2, 'hint' => __('site.profile_page.photo_hint'), 'kind' => 'image',
            ])
            @error('photo')<div class="auth-error">{{ $message }}</div>@enderror

            <label for="profile-bio">{{ __('site.profile_page.bio') }}</label>
            @include('profile._editor', ['id' => 'profile-bio', 'name' => 'bio', 'class' => 'profile-bio-editor', 'value' => old('bio', $user->bio), 'label' => __('site.profile_page.bio')])
            @error('bio')<div class="auth-error">{{ $message }}</div>@enderror

            <label for="profile-homepage">{{ __('site.profile_page.homepage') }}</label>
            <input id="profile-homepage" name="homepage_url" type="url" value="{{ old('homepage_url', $user->homepage_url) }}">
            @error('homepage_url')<div class="auth-error">{{ $message }}</div>@enderror

            <label for="profile-orcid">Orcid ID</label>
            <input id="profile-orcid" name="orcid" type="text" placeholder="0000-0000-0000-0000" value="{{ old('orcid', $user->orcid) }}">
            @error('orcid')<div class="auth-error">{{ $message }}</div>@enderror
            @include('profile._footer', ['required' => true])
        </form>
    </section>
@endsection
