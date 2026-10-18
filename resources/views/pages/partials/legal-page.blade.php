@include('pages.partials.breadcrumb')
<section class="py-12">
    <div class="container">
        <div class="cms-content max-w-4xl mx-auto">
            {!! clean_user_html($page->{'content_'.app()->getLocale()}) !!}
        </div>
    </div>
</section>

@include('pages.partials.cms-content-styles')
