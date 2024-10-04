<?php

namespace App\Traits;

trait FileTrait
{
    public function uploadFile($object, $file, $collection)
    {
      return  $object->addMedia($file)->toMediaCollection($collection);
    }


    public function deleteFile($object, $collection): void
    {
        $object->clearMediaCollection($collection);

    }
}
