{{-- $image: path under assets/images/empty, $message --}}
<section class="empty-section container my-4">
    <div class="empty-content">
        <div class="empty-illustration">
            <img src="{{ asset('assets/images/empty/'.$image) }}" alt="">
        </div>
        <p>{{ $message }}</p>
    </div>
</section>
