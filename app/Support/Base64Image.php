<?php

namespace App\Support;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class Base64Image
{
    public static function toJpegBinary(string $dataUrl, int $quality = 90): string
    {
        $manager = new ImageManager(new Driver());

        // `read()` understands data URLs (data:image/...;base64,...) and raw binary.
        $image = $manager->read($dataUrl);

        return $image->toJpeg(quality: $quality)->toString();
    }
}

