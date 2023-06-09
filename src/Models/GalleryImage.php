<?php

namespace Aw3r1se\LaravelUtilities\Models;

class GalleryImage extends Image
{
    protected $fillable = [
        'file_name',
        'folder',
        'alt',
        'alt_en',
        'sort',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!empty($model->sort)) {
                return;
            }
            $model->sort = static::where('model_type', $model->model_type)
                    ->where('model_id', $model->model_id)
                    ->max('sort') + 1;
        });
    }
}
