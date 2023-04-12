<?php

namespace Awe3r1se\UtilityTraits\Traits;

use Aw3r1se\UtilityTraits\DTO\ImageDTO;
use Awe3r1se\UtilityTraits\Models\GalleryImage;
use Awe3r1se\UtilityTraits\Models\Image;
use Aw3r1se\UtilityTraits\Services\ImageService;
use Aw3r1se\UtilityTraits\Services\MediaService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

trait ImagesTrait
{
    use MediaTrait;

    public $needDeleteImage = false;
    public $needUploadImage = null;

    public array $needDeleteGalleryImages = [];
    public array $needUploadGalleryImages = [];
    public array $needUpdateGallery = [];

    public function fillImagesTrait(array $attributes): void
    {
        if (!empty($attributes['deleteImage'])) {
            $this->needDeleteImage = true;
            unset($attributes['deleteImage']);
        }

        if (!empty($attributes['uploadImage'])) {
            $this->needUploadImage = $attributes['uploadImage'];
            unset($attributes['uploadImage']);
        }

        if (!empty($attributes['deleteGalleryImages'])) {
            $this->needDeleteGalleryImages = $attributes['deleteGalleryImages'];
        }

        if (!empty($attributes['uploadGalleryImages'])) {
            $this->needUploadGalleryImages = $attributes['uploadGalleryImages'];
        }

        if (!empty($attributes['updateGallery'])) {
            $this->needUpdateGallery = $attributes['updateGallery'];
        }
    }

    public function getImageThumbs()
    {
        return [
            'admin' => [ImageService::CROP, 320, 240],
            'small' => [ImageService::CROP, 122, 122],
            'thumb' => [ImageService::CROP, 200, 200],
            'big' => [ImageService::CROP, 600, 600],
        ];
    }

    public function getGalleryThumbs()
    {
        return [
            'admin' => [ImageService::CROP, 320, 240],
            'small' => [ImageService::CROP, 122, 122],
            'thumb' => [ImageService::CROP, 200, 200],
            'big' => [ImageService::CROP, 600, 600],
        ];
    }

    protected function imageThumbs(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->convertThumbsForFront($this->getImageThumbs())
        )->shouldCache();
    }

    protected function galleryThumbs(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->convertThumbsForFront($this->getGalleryThumbs())
        )->shouldCache();
    }

    protected function convertThumbsForFront($thumbsInfo)
    {
        return collect($thumbsInfo)->map(function ($item, $key) {
            return [
                'name' => $key,
                'method' => $item[0] ?? null,
                'width' => $item[1] ?? null,
                'height' => $item[2] ?? null,
            ];
        });
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image?->url,
        )->shouldCache();
    }

    public static function bootImagesTrait()
    {
        static::deleting(function ($model) {
            $model->image?->delete();

            $model->gallery()->delete();
            $galleryFolder = ImageService::pathGenerate($model, ImageService::GALLERY);
            Storage::disk('public')->deleteDirectory($galleryFolder);
        });

        static::saved(function ($model) {
            if ($model->needDeleteImage) {
                $model->image?->delete();
            }
            if ($model->needUploadImage) {
                $model->image?->delete();
                $model->addImageFromRequest($model->needUploadImage);
            }
            if ($model->needDeleteGalleryImages) {
                $model->gallery()
                    ->whereIn('id', $model->needDeleteGalleryImages)
                    ->each(fn ($media) => $media->delete());
            }
            if ($model->needUpdateGallery) {
                foreach ($model->needUpdateGallery as $item) {
                    $model->gallery()
                        ->where('id', (int)$item['id'])
                        ->update($item);
                }
            }
            if ($model->needUploadGalleryImages) {
                foreach ($model->needUploadGalleryImages as $uplaodImage) {
                    $model->addGalleryImageFromRequest($uplaodImage);
                }
            }
        });
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'model');
    }

    public function gallery(): morphMany
    {
        return $this->morphMany(GalleryImage::class, 'model');
    }

    public function addImageFromRequest(UploadedFile $file)
    {
        $imageDTO = new ImageDTO($file);
        $imageDTO->filename = ImageService::fileNameGenerate($file);

        return $this->addImage($imageDTO);
    }

    public function addImageFromPath(string $file)
    {
        $imageDTO = new ImageDTO($file);
        $imageDTO->filename = mb_strtolower(basename($file));

        return $this->addImage($imageDTO);
    }

    public function addGalleryImageFromPath(string $file)
    {
        $imageDTO = new ImageDTO($file, ImageService::GALLERY);
        $imageDTO->filename = mb_strtolower(basename($file));

        return $this->addImage($imageDTO);
    }

    public function addGalleryImageFromRequest($uploadImage)
    {
        $upload = $uploadImage['upload'];

        $imageDTO = new ImageDTO($upload, ImageService::GALLERY);
        $imageDTO->filename = ImageService::fileNameGenerate($upload);
        $imageDTO->alt = $uploadImage['alt'] ?? '';
        $imageDTO->sort = $uploadImage['sort'] ?? null;

        return $this->addImage($imageDTO);
    }

    public function addImage(ImageDTO $imageDTO)
    {
        $imageDTO->folder = ImageService::createFolder($this, $imageDTO->type);

        $thumbs = $imageDTO->isGallery() ? $this->getGalleryThumbs() : $this->getImageThumbs();

        $imageDTO = ImageService::saveImageWithThumbs($imageDTO, $thumbs);

        // Обновляем время изменение сущности, предварительно проверив что сущность не обновилась только что
        // чтобы избежать лишнего запроса в базу
        if ($this->updated_at < now()->subMinute()) {
            $this->setUpdatedAt(now())->saveQuietly();
        }

        $attributes = $imageDTO->toArray();

        return $imageDTO->isGallery() ? $this->gallery()->create($attributes) : $this->image()->create($attributes);
    }

    /**
     * Получить путь папки где лежат изображения галереи
     *
     * @return string|null
     */
    public function getGalleryFolder(): ?string
    {
        return MediaService::pathGenerate($this, ImageService::GALLERY);
    }

    /**
     * Получить путь папки где лежат изображения
     *
     * @return string|null
     */
    public function getImageFolder(): ?string
    {
        return MediaService::pathGenerate($this, ImageService::IMAGE);
    }

    /**
     * Удаляет вариации оригинальных изображений из галереи сущности
     *
     * @return void
     */
    public function deleteGalleryImagesThumbs(): void
    {
        $gallery_images = $this->gallery;
        if (empty($gallery_images)) {
            return;
        }
        $disk = MediaService::getDisk();
        $gallery_images = $gallery_images->append('path');
        $gallery_original_images_paths = $gallery_images->pluck('path');
        $all_files = $disk->files($this->getGalleryFolder());
        foreach ($all_files as $file) {
            if ($gallery_original_images_paths->doesntContain($file)) {
                $disk->delete($file);
            }
        }
    }

    /**
     * Пересоздает вариации оригинальных изображений из галереи сущности
     *
     * @return void
     */
    public function generateGalleryImagesThumbs(): void
    {
        foreach ($this->gallery ?? [] as $image) {
            ImageService::makeThumbs(ImageDTO::makeFromImage($image), $this->getImageThumbs());
        }
    }

    /**
     * Удаляет вариации оригинального изображения из изображения сущности
     *
     * @return void
     */
    public function deleteImageThumbs(): void
    {
        if (empty($this->image)) {
            return;
        }
        $disk = MediaService::getDisk();
        $all_files = $disk->files($this->getImageFolder());
        foreach ($all_files as $file) {
            if ($file !== $this->image->path) {
                $disk->delete($file);
            }
        }
    }

    /**
     * Пересоздает вариации оригинального изображения из изображения сущности
     *
     * @return void
     */
    public function generateImageThumbs(): void
    {
        if (!empty($this->image)) {
            ImageService::makeThumbs(ImageDTO::makeFromImage($this->image), $this->getImageThumbs());
        }
    }

    /**
     * Получить пути разрешений изображения
     *
     * @param ImageDTO $imageDTO
     * @param string $type
     * @return array
     */
    public function makeThumbsPaths(ImageDTO $imageDTO, string $type = ImageService::IMAGE): array
    {
        $images_paths = [];

        if ($type === ImageService::GALLERY) {
            $thumbs = $this->getGalleryThumbs();
        } else {
            $thumbs = $this->getImageThumbs();
        }

        foreach ($thumbs as $key => $thumb_size) {
            $images_paths[] = MediaService::makeThumbPath($imageDTO, $key);
            $images_paths[] = MediaService::makeRetinaThumbPath($imageDTO, $key);
        }

        return $images_paths;
    }

    /**
     * Получить список путей разрешений изображений
     *
     * @return array
     */
    public function makeImageThumbsPaths(): array
    {
        if (!empty($this->image)) {
            $imageDTO = ImageDTO::makeFromImage($this->image);
            return $this->makeThumbsPaths($imageDTO);
        }
        return [];
    }

    /**
     * Получить список путей разрешений изображений
     *
     * @return array
     */
    public function makeGalleryThumbsPaths(): array
    {
        $images_path = [];
        $gallery_images = $this->gallery;

        if (empty($gallery_images)) {
            return [];
        }

        foreach ($gallery_images as $file) {
            $imageDTO = ImageDTO::makeFromImage($file);
            $images_path[] = $this->makeThumbsPaths($imageDTO, ImageService::GALLERY);
        }

        return Arr::collapse($images_path);
    }
}
