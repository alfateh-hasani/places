<?php

namespace App\Traits;
use Artesaos\SEOTools\Facades\SEOTools;
trait generateSeoTrait
{

    public function generateSeo($seo_title, $seo_description,$url)
    {
        SEOTools::setTitle($seo_title);
        SEOTools::setDescription($seo_description);
        SEOTools::opengraph()->setUrl($url);
        SEOTools::setCanonical($url);
        SEOTools::opengraph()->addProperty('type', 'articles');

    }
}
