<?php

namespace Aw3r1se\LaravelUtilities\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'folder',
        'file_name',
        'user_name',
        'sort',
    ];

    protected $appends = ['url'];

    protected static function booted(): void
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

    protected function url(): Attribute
    {
        return Attribute::make(
            get: function (?string $url) {
                return $url
                    ? $url
                    : Storage::disk('public')->url($this->folder . $this->file_name);
            }
        )->shouldCache();
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->folder . $this->file_name
        );
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function delete(): ?bool
    {
        Storage::disk('public')
            ->delete($this->folder . $this->file_name);

        return parent::delete();
    }
}
