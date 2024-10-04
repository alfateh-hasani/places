<?php
function getImage($object, $collection, $thumb=''){
    if ($object->hasMedia($collection)){
        return $object->getMedia($collection)->first()->getUrl($thumb);
    }
    return asset('place_holder.svg');
}

function convertArabicNumbers($input): array|string
{
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($arabic, $english, $input);
}
