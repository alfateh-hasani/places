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
