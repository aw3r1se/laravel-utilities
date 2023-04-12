<?php

namespace Aw3r1se\LaravelUtilities\Traits;

trait MediaTrait
{
    public function fill(array $attributes): static
    {
        foreach (trait_uses_recursive($this) as $trait) {
            $method = 'fill' . class_basename($trait);
            if (method_exists($this, $method)) {
                $this->$method($attributes);
            }
        }

        return parent::fill($attributes);
    }
}
