<?php

namespace Aw3r1se\LaravelUtilities\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class RestApiResource extends JsonResource
{
    public array $appends = [];

    /**
     * Поля всегда присутсвующие в выдаче
     * @return array
     */
    public function toDefaultArray(): array
    {
        return ['id' => $this->id];
    }

    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $result = $this->toDefaultArray();

        foreach ($this->appends as $with_item) {
            $method = Str::camel("append_$with_item");
            if (method_exists($this, $method)) {
                $result = $this->$method($result);
            }
        }

        return $result;
    }

    /**
     * @param mixed $resource
     * @param array $appends
     * @return RestApiCollection
     */
    public static function collection($resource, array $appends = []): RestApiCollection
    {
        return tap(new RestApiCollection($resource, static::class), function ($collection) use ($appends) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
            $collection->appends = $appends;
        });
    }

    /**
     * @param array $result
     * @return mixed
     */
    public function appendTimestamps(array $result): array
    {
        $result['createdAt'] = $this->created_at ?? null;
        $result['updatedAt'] = $this->updated_at ?? null;

        return $result;
    }

    /**
     * @param array $result
     * @return mixed
     */
    public function appendContent(array $result): array
    {
        $result['content'] = $this->content ?? null;

        return $result;
    }

    /**
     * @param array $result
     * @return mixed
     */
    public function appendImage(array $result): array
    {
        $result['image'] = $this->image;
        return $result;
    }

    /**
     * @param array $result
     * @return mixed
     */
    public function appendGallery(array $result): array
    {
        $result['gallery'] = $this->gallery;

        return $result;
    }

    /**
     * Зарезервированный параметр - возврат всех свойств
     * @param array $result
     * @return array
     */
    public function appendFull(array $result): array
    {
        $result = $this->appendContent($result);
        $result = $this->appendImage($result);
        $result = $this->appendGallery($result);

        return $this->appendTimestamps($result);
    }

    /**
     * @param string|array $appends
     * @return $this
     */
    public function addAppends(string|array $appends): static
    {
        if (is_string($appends)) {
            $appends = explode(',', $appends);
        }
        $this->appends = array_merge($this->appends, $appends);

        return $this;
    }
}
