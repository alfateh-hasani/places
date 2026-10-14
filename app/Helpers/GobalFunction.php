<?php
function getImage($object, $collection, $thumb=''){
    if ($object and $object->hasMedia($collection)){
        return $object->getMedia($collection)->first()->getUrl($thumb);
    }
    return asset('img/default.jpg');
}

//getAllImages
function getAllImages($object, $collection, $thumb='', $limit = null){
    if ($object->hasMedia($collection)){
        $media = $object->getMedia($collection);
        if ($limit !== null) {
            $media = $media->take($limit);
        }
        return $media->map(function ($media) use ($thumb){
            return $media->getUrl($thumb);
        });
    }
    return [asset('img/default.jpg')]; 
}
function convertArabicNumbers($input): array|string
{
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($arabic, $english, $input);
}


//remove_http
function remove_http($url): string
{
    return str_replace(['http://', 'https://'], '', $url);
}

use Illuminate\Support\Facades\Config;

if (!function_exists('calculateTax')) {

    function calculateTax($amount)
    {
        $taxRate = Cache::remember('settings.tax', 60, function () {
            return Config::get('settings.tax', 15);  
        });
        $decimalTaxRate = $taxRate / 100;  
        return $amount * $decimalTaxRate;
    }
}

if (!function_exists('calculateTotalWithTax')) {
    function calculateTotalWithTax($amount)
    {
        return $amount;
    }
}

if (!function_exists('clean_user_html')) {
    /**
     * Dependency-free sanitizer for admin-authored rich text rendered with {!! !!}.
     * Removes script/style/iframe/object/embed/etc. elements, on* event-handler attributes,
     * inline styles, srcdoc, and javascript:/data:/vbscript: URIs. Keeps basic formatting.
     * Not a full HTMLPurifier, but strips the practical stored-XSS vectors.
     */
    function clean_user_html(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"?><div data-sanitizer-root>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $blockedTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'link', 'meta', 'base', 'applet', 'frame', 'frameset', 'svg', 'math'];
        foreach ($blockedTags as $tag) {
            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $uriAttributes = ['href', 'src', 'xlink:href', 'action', 'formaction', 'background', 'poster', 'data'];
        foreach (iterator_to_array($dom->getElementsByTagName('*')) as $element) {
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);
                $value = trim((string) $attribute->nodeValue);

                if (str_starts_with($name, 'on') || $name === 'style' || $name === 'srcdoc') {
                    $element->removeAttribute($attribute->nodeName);
                } elseif (in_array($name, $uriAttributes, true) && preg_match('/^\s*(javascript|data|vbscript):/i', $value)) {
                    $element->removeAttribute($attribute->nodeName);
                }
            }
        }

        $root = $dom->getElementsByTagName('div')->item(0);
        if (! $root) {
            return '';
        }

        $clean = '';
        foreach ($root->childNodes as $child) {
            $clean .= $dom->saveHTML($child);
        }

        return $clean;
    }
}
