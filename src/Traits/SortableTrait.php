<?php

namespace Aw3r1se\LaravelUtilities\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait SortableTrait
{
    /**
     * Сортировка данных на основе параметра вида "столбец-направление"
     */
    public function scopeSort(Builder $builder, $sort): Builder
    {
        $a_sort = explode('-', $sort);

        $column = Str::snake($a_sort[0]);
        $direction = $a_sort[1] ?? 'asc';

        if ($direction === 'ascending') {
            $direction = 'asc';
        }

        if ($direction === 'descending') {
            $direction = 'desc';
        }

        if (!in_array(Str::lower($direction), ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $allowed = array_merge($this->fillable, ['id', 'created_at', 'updated_at']);

        $method = Str::camel("sort_$column");
        if (method_exists($this, $method)) {
            $this->$method($builder, $direction);
        } elseif (in_array($column, $allowed)) {
            $builder->reorder($column, $direction);
        }

        return $builder;
    }
}
