@include('pages.partials.breadcrumb')
<section class="py-12 container">
        {!! $page->{'content_'.app()->getLocale()} !!}
</section>
