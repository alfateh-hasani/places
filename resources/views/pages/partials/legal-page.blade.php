@include('pages.partials.breadcrumb')
<section class="py-12">
    <div class="container">
        <div class="legal-content max-w-4xl mx-auto">
            {!! $page->{'content_'.app()->getLocale()} !!}
        </div>
    </div>
</section>

<style>
    /* The CMS content is raw HTML with no styling, so it renders cramped and
       hard to read. Give it readable prose typography (spacing, heading
       hierarchy, lists, links) without depending on a Tailwind typography build. */
    .legal-content {
        line-height: 1.9;
    }
    /* The CMS HTML ships black text, which is invisible on the dark page.
       Force readable colors (light body, white headings, orange links). The
       !important overrides any inline color the editor saved on the content. */
    .legal-content,
    .legal-content p,
    .legal-content li,
    .legal-content span,
    .legal-content div,
    .legal-content td,
    .legal-content th {
        color: #e5e5e5 !important;
    }
    .legal-content h1,
    .legal-content h2,
    .legal-content h3,
    .legal-content h4,
    .legal-content h5,
    .legal-content strong,
    .legal-content b {
        color: #ffffff !important;
    }
    .legal-content a {
        color: #f7bb8e !important;
    }
    .legal-content > :first-child {
        margin-top: 0;
    }
    .legal-content h1,
    .legal-content h2,
    .legal-content h3,
    .legal-content h4,
    .legal-content h5 {
        font-weight: 700;
        line-height: 1.4;
        margin: 2rem 0 0.75rem;
    }
    .legal-content h1 { font-size: 1.75rem; }
    .legal-content h2 { font-size: 1.5rem; }
    .legal-content h3 { font-size: 1.25rem; }
    .legal-content h4 { font-size: 1.125rem; }
    .legal-content p {
        margin-bottom: 1rem;
    }
    .legal-content ul,
    .legal-content ol {
        padding-inline-start: 1.5rem;
        margin-bottom: 1rem;
    }
    .legal-content ul { list-style: disc; }
    .legal-content ol { list-style: decimal; }
    .legal-content li { margin-bottom: 0.5rem; }
    .legal-content a {
        color: #f7bb8e;
        text-decoration: underline;
    }
    .legal-content img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
    }
    .legal-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    .legal-content th,
    .legal-content td {
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 0.5rem 0.75rem;
        text-align: start;
    }
</style>
