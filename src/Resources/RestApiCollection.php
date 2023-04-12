<?php

namespace Aw3r1se\LaravelUtilities\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class RestApiCollection extends AnonymousResourceCollection
{
    public bool $noWrap = false;
    public array $appends = [];

    /**
     * @param $request
     * @return Collection|array
     */
    public function toArray($request): Collection|array
    {
        if ($this->appends) {
            foreach ($this->collection as $item) {
                $item->appends = $this->appends;
            }
        }

        if (empty(static::$wrap) || $this->noWrap) {
            return $this->collection;
        }

        $result = [static::$wrap => $this->collection];

        if (
            $this->resource instanceof AbstractPaginator
            || $this->resource instanceof AbstractCursorPaginator
        ) {
            $result['pagination'] = [
                'perPage' => (int)$this->perPage(),
                'currentPage' => (int)$this->currentPage(),
                'lastPage' => (int)$this->lastPage(),
                'countItems' => (int)$this->total()
            ];
        }

        return $result;
    }

    /**
     * @param $resource
     * @param string $collects
     */
    public function __construct($resource, string $collects = 'Illuminate\Http\Resources\Json\JsonResource')
    {
        parent::__construct($resource, $collects);
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function toResponse($request): JsonResponse
    {
        return (new ResourceResponse($this))->toResponse($request);
    }

    /**
     * @param $appends
     * @return $this
     */
    public function appends($appends): static
    {
        $this->appends = $appends;

        return $this;
    }

    /**
     * @return $this
     */
    public function noWrap(): static
    {
        $this->noWrap = true;

        return $this;
    }
}
