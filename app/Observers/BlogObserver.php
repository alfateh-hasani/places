<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\SlugRedirect;

class BlogObserver
{
    /**
     * Keep the blog post's old public URL alive by recording a redirect whenever its slug is renamed.
     */
    public function updating(Blog $blog): void
    {
        if (! $blog->isDirty('slug')) {
            return;
        }

        $oldSlug = $blog->getOriginal('slug');

        if (empty($oldSlug) || $oldSlug === $blog->slug) {
            return;
        }

        SlugRedirect::updateOrCreate(
            [
                'redirectable_type' => Blog::class,
                'old_slug' => $oldSlug,
            ],
            [
                'redirectable_id' => $blog->id,
            ]
        );
    }
}
