<a class="announcement-card-link" href="{{ route('announcements.show', $announcement) }}">
    <article class="announcement-card">
        <img class="announcement-img" src="{{ $announcement->image_url ?? asset('assets/images/announcements/announcement-1.png') }}" alt="">
        <div class="announcement-body">
            <div class="announcement-meta"><i class="bi bi-calendar3"></i> {{ $announcement->published_at->format('d.m.Y') }} &nbsp; <i class="bi bi-eye"></i> {{ $announcement->views_count }}</div>
            <h3 class="announcement-title">{{ $announcement->title }}</h3>
        </div>
    </article>
</a>
