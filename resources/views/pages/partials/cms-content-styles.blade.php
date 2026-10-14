{{--
    Shared prose typography for admin-authored CMS HTML (blog posts, legal/policy
    pages). The sanitizer (clean_user_html) strips inline styles, so these rules
    provide readable spacing, heading hierarchy, lists, links, images and tables
    without depending on a Tailwind typography build. The site renders dark, so
    colors are forced light with !important to override any color the editor saved.
--}}
<style>
    .cms-content {
        line-height: 1.9;
    }
    .cms-content,
    .cms-content p,
    .cms-content li,
    .cms-content span,
    .cms-content div,
    .cms-content td,
    .cms-content th {
        color: #e5e5e5 !important;
    }
    .cms-content h1,
    .cms-content h2,
    .cms-content h3,
    .cms-content h4,
    .cms-content h5,
    .cms-content strong,
    .cms-content b {
        color: #ffffff !important;
    }
    .cms-content a {
        color: #f7bb8e !important;
    }
    .cms-content > :first-child {
        margin-top: 0;
    }
    .cms-content h1,
    .cms-content h2,
    .cms-content h3,
    .cms-content h4,
    .cms-content h5 {
        font-weight: 700;
        line-height: 1.4;
        margin: 2rem 0 0.75rem;
    }
    .cms-content h1 { font-size: 1.75rem; }
    .cms-content h2 { font-size: 1.5rem; }
    .cms-content h3 { font-size: 1.25rem; }
    .cms-content h4 { font-size: 1.125rem; }
    .cms-content p {
        margin-bottom: 1rem;
    }
    .cms-content ul,
    .cms-content ol {
        padding-inline-start: 1.5rem;
        margin-bottom: 1rem;
    }
    .cms-content ul { list-style: disc; }
    .cms-content ol { list-style: decimal; }
    .cms-content li { margin-bottom: 0.5rem; }
    .cms-content a {
        text-decoration: underline;
    }
    .cms-content img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
    }
    .cms-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    .cms-content th,
    .cms-content td {
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 0.5rem 0.75rem;
        text-align: start;
    }
</style>
