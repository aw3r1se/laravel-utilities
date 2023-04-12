<?php

namespace Aw3r1se\UtilityTraits\Services;

use Aw3r1se\UtilityTraits\DTO\ImageDTO;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public static function getDisk(): Filesystem
    {
        return Storage::disk('public');
    }

    public static function fileNameGenerate(UploadedFile $file): string
    {
        $base_name = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        return mb_strtolower(Str::slug($base_name) . '.' . $extension);
    }

    public static function pathGenerate(Model $model, ?string $type = null): string
    {
        $hash = $model->getTable();
        $key = $model->getKey();
        $path = Str::padLeft(floor($key / 100), 2, '0');

        return ($type ?? static::$type) . '/' . $hash . '/' . $path . '/' . $key . '/';
    }

    public static function createFolder(Model $model, ?string $type = null): string
    {
        $result = static::pathGenerate($model, $type ?? static::$type);
        Storage::disk('public')->makeDirectory($result);

        return $result;
    }

    /**
     * @param $filename
     * @param $thumb
     * @param $extension string|null если null расширение не меняется
     * @return string
     */
    public static function thumbNameGenerate($filename, $thumb, ?string $extension = null): string
    {
        $pos = strrpos($filename, '.');

        $extension ??= substr($filename, $pos + 1);
        $filename = substr($filename, 0, $pos);

        return $filename . '-' . $thumb . '.' . $extension;
    }

    protected static function replaceExtension(string $filename, string $newExtension): string
    {
        $filename = substr($filename, 0, (strrpos($filename, '.')));

        return $filename . '.' . $newExtension;
    }

    /**
     * Возвращает путь нового разрешения изображения
     *
     * @param ImageDTO $imageDTO
     * @param string $key
     * @return string
     */
    public static function makeThumbPath(ImageDTO $imageDTO, string $key): string
    {
        return $imageDTO->folder . self::thumbNameGenerate($imageDTO->filename, $key, 'webp');
    }

    /**
     * Возвращает путь нового разрешения Retina изображения
     *
     * @param ImageDTO $imageDTO
     * @param string $key
     * @return string
     */
    public static function makeRetinaThumbPath(ImageDTO $imageDTO, string $key): string
    {
        return $imageDTO->folder . self::thumbNameGenerate($imageDTO->filename, $key . '@2x', 'webp');
    }
}
