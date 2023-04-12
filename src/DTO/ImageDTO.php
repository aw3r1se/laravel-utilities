<?php

namespace Aw3r1se\LaravelUtilities\DTO;

use Aw3r1se\LaravelUtilities\Models\GalleryImage;
use Aw3r1se\LaravelUtilities\Models\Image;
use Aw3r1se\LaravelUtilities\Services\ImageService;

class ImageDTO
{
    public $source;
    public $type = ImageService::IMAGE;
    public $filename = '';
    public $folder = '';
    public $alt = '';
    public $sort = null;
    public $img;

    public function __construct($source, $type = ImageService::IMAGE)
    {
        $this->source = $source;
        $this->type = $type;
        $this->img = \Image::make($this->source);
    }

    public function isGallery(): bool
    {
        return $this->type == ImageService::GALLERY;
    }

    public function toArray(): array
    {
        $result = [
            'file_name' => $this->filename,
            'folder' => $this->folder,
        ];

        if ($this->alt) {
            $result['alt'] = $this->alt;
        }
        if ($this->sort) {
            $result['sort'] = $this->sort;
        }
        return $result;
    }

    public static function makeFromImage(Image $image): self
    {
        if ($image instanceof GalleryImage) {
            $type = ImageService::GALLERY;
        } else {
            $type = ImageService::IMAGE;
        }
        $image_dto = new self($image->absolute_path, $type);
        $image_dto->filename = $image->file_name;
        $image_dto->folder = $image->folder;
        return $image_dto;
    }
}
