<?php

namespace Aw3r1se\LaravelUtilities\Models;

use Aw3r1se\LaravelUtilities\Services\MediaService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $folder
 * @property string $file_name
 * @property string $path
 * @property string $absolute_path
 */
class Image extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'file_name',
        'folder',
    ];

    protected $appends = ['url'];

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => MediaService::getDisk()->url($this->folder.$this->file_name),
        )->shouldCache();
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function delete()
    {
        //TODO: Удалять дополнительно все размеры
        MediaService::getDisk()->delete($this->folder.$this->file_name);
        parent::delete();
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->folder . $this->file_name
        );
    }
    protected function absolutePath(): Attribute
    {
        return Attribute::make(
            get: fn () => MediaService::getDisk()->path($this->path)
        );
    }
}
