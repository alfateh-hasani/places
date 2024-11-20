<?php
function getImage($object, $collection, $thumb=''){
    if ($object->hasMedia($collection)){
        return $object->getMedia($collection)->first()->getUrl($thumb);
    }
    return asset('img/default.jpg');
}

//getAllImages
function getAllImages($object, $collection, $thumb=''){
    if ($object->hasMedia($collection)){
        return $object->getMedia($collection)->map(function ($media) use ($thumb){
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
        return $amount + calculateTax($amount);
    }
}
