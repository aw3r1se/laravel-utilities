<?php

namespace Aw3r1se\LaravelUtilities\Services;

use Aw3r1se\LaravelUtilities\DTO\ImageDTO;
use Intervention\Image\Image;

class ImageService extends MediaService
{
    public const CROP = 'crop';
    public const CONTAIN = 'contain';
    private static string $background_color = 'fff';
    public const IMAGE = 'images';
    public const GALLERY = 'gallery';
    protected static string $type = self::IMAGE;

    public static function makeThumb($img, $size): Image
    {
        list($method, $width, $height) = $size;

        if ($method == ImageService::CROP) {
            return self::crop(clone $img, $width, $height);
        }

        if ($method === ImageService::CONTAIN) {
            return self::contain(clone $img, $width, $height);
        }

        return $img;
    }

    public static function crop(Image $img, int $width, int $height): Image
    {
        return $img->fit($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });
    }

    public static function contain(Image $img, int $width, int $height): Image
    {
        return $img
            ->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            })
            ->resizeCanvas($width, $height, 'center', false, self::$background_color);
    }

    public static function saveImageWithThumbs(ImageDTO $imageDTO, $thumbsInfo)
    {
        // Проверяем наличие уже такого файла (чтобы не затереть существующий)
        $path = $imageDTO->folder . $imageDTO->filename;
        $originalFilename = $imageDTO->filename;
        $index = 1;

        while (self::getDisk()->exists($path)) {
            $index++;
            $imageDTO->filename = self::thumbNameGenerate($originalFilename, $index, 'webp');
            $path = $imageDTO->folder . $imageDTO->filename;
        }

        $imageDTO->img->save(self::getDisk()->path($path));

        self::makeThumbs($imageDTO, $thumbsInfo);

        return $imageDTO;
    }

    public static function makeThumbs(ImageDTO $imageDTO, $thumbsInfo)
    {
        foreach ($thumbsInfo as $key => $thumb_size) {
            $thumbPath = self::makeThumbPath($imageDTO, $key);
            ImageService::makeThumb($imageDTO->img, $thumb_size)->save(self::getDisk()->path($thumbPath), 90, 'webp');

            $thumb_size[1] *= 2;
            $thumb_size[2] *= 2;

            $retinaPath = self::makeRetinaThumbPath($imageDTO, $key);
            ImageService::makeThumb($imageDTO->img, $thumb_size)->save(self::getDisk()->path($retinaPath), 90, 'webp');
        }
    }
}
