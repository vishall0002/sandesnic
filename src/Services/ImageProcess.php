<?php

namespace App\Services;

class ImageProcess
{
    public function resizeEmployeePhoto($strSourceImage)
    {
        $thumbImage = null;
        $thumbnail = null;
        $width = 150;
        $height = 200;

        $imageParams = getimagesizefromstring($strSourceImage);
        $w = $imageParams[0];
        $h = $imageParams[1];

        if (!$w) {
            return 'Unsupported picture type!';
        }

        $type = strtolower($imageParams['mime']);

        $sourceImage = imagecreatefromstring($strSourceImage);

        if ($w < $width and $h < $height) {
            return 'Picture is too small!';
        }
        $ratio = min($width / $w, $height / $h);
        $width = $w * $ratio;
        $height = $h * $ratio;
        $x = 0;

        $thumbImage = imagecreatetruecolor($width, $height);

        // preserve transparency
        if ('image/gif' == $type or 'image/png' == $type) {
            imagecolortransparent($thumbImage, imagecolorallocatealpha($thumbImage, 0, 0, 0, 127));
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
        }

        imagecopyresampled($thumbImage, $sourceImage, 0, 0, $x, 0, $width, $height, $w, $h);
        ob_start();
        switch ($type) {
            case 'image/bmp':
                imagewbmp($thumbImage, null);
                break;
            case 'image/gif':
                imagegif($thumbImage, null);
                break;
            case 'image/jpg':
                imagejpeg($thumbImage, null);
                break;
            case 'image/png':
                imagepng($thumbImage, null);
                break;
        }
        $final_image = ob_get_contents();

        ob_end_clean();
        return $final_image;
    }

    public function generateThumbnail($strSourceImage)
    {
        $thumbImage = null;
        $thumbnail = null;
        $width = 72;
        $height = 96;

        $imageParams = getimagesizefromstring($strSourceImage);
        $w = $imageParams[0];
        $h = $imageParams[1];

        if (!$w) {
            return null;
        }

        $type = strtolower($imageParams['mime']);

        $sourceImage = imagecreatefromstring($strSourceImage);

        if ($w < $width and $h < $height) {
            return null;
        }
        $ratio = min($width / $w, $height / $h);
        $width = $w * $ratio;
        $height = $h * $ratio;
        $x = 0;

        $thumbImage = imagecreatetruecolor($width, $height);

        // preserve transparency
        if ('image/gif' == $type or 'image/png' == $type) {
            imagecolortransparent($thumbImage, imagecolorallocatealpha($thumbImage, 0, 0, 0, 127));
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
        }

        imagecopyresampled($thumbImage, $sourceImage, 0, 0, $x, 0, $width, $height, $w, $h);
        ob_start();
        switch ($type) {
            case 'image/bmp':
                imagewbmp($thumbImage, null);
                break;
            case 'image/gif':
                imagegif($thumbImage, null);
                break;
            case 'image/jpg':
                imagejpeg($thumbImage, null);
                break;
            case 'image/jpeg':
                imagejpeg($thumbImage, null);
                break;
            case 'image/png':
                imagepng($thumbImage, null);
                break;
        }
        $final_image = ob_get_contents();

        ob_end_clean();
        return $final_image;
    }

}
