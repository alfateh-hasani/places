<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Organizes media into a predictable, domain-grouped S3 layout instead of the
 * flat `{media_id}/{file}` default.
 *
 * Layout:
 *   public/...   → served publicly (bucket policy grants read on `public/*`)
 *   private/...  → NOT publicly readable; served via signed temporary URLs
 */
class DomainPathGenerator implements PathGenerator
{
    /**
     * model_type => prefix. Prefix may contain `{id}` and `{collection}` tokens.
     *
     * @var array<class-string, string>
     */
    private const MAP = [
        \App\Models\Apartment::class      => 'public/apartments/{id}/{collection}',
        \App\Models\Building::class       => 'public/buildings/{id}/images',
        \App\Models\Customer::class       => 'public/customers/{id}/profile',
        \App\Models\Slider::class         => 'public/content/sliders/{id}/{collection}',
        \App\Models\SliderApp::class      => 'public/content/slider-apps/{id}/{collection}',
        \App\Models\Blog::class           => 'public/content/blogs/{id}',
        \App\Models\Page::class           => 'public/content/pages/{id}',
        \App\Models\City::class           => 'public/content/cities/{id}',
        \App\Models\Onboarding::class     => 'public/content/onboarding/{id}',
        \App\Models\Notification::class   => 'public/content/notifications/{id}',
        \App\Models\Feature::class        => 'public/content/icons/features/{id}',
        \App\Models\SiteFeature::class    => 'public/content/icons/site-features/{id}',
        \App\Models\Advantage::class      => 'public/content/icons/advantages/{id}',
        \App\Models\ApartmentLabel::class => 'public/content/icons/apartment-labels/{id}',
        \App\Models\Transaction::class    => 'private/transactions/{id}/receipts',
    ];

    public function getPath(Media $media): string
    {
        return $this->basePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media).'/responsive-images/';
    }

    private function basePath(Media $media): string
    {
        $prefix = self::MAP[$media->model_type] ?? 'misc/{id}';

        $prefix = str_replace(
            ['{id}', '{collection}'],
            [(string) $media->model_id, $media->collection_name],
            $prefix,
        );

        return $prefix.'/'.$media->getKey();
    }
}
