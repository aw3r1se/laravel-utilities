<?php

namespace Aw3r1se\LaravelUtilities\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 *
 * @method static Builder|$this filter(array $filter = [])
 */
trait FilterableTrait
{
    /**
     * Фильтрация при использовании GraphQL
     *
     * @param Builder $builder
     * @param array $filters
     * @return Builder
     * @throws Exception
     */
    public function scopeMappedFilter(Builder $builder, array $filters): Builder
    {
        $mapped = [];
        foreach ($filters as $filter) {
            $mapped[$filter['column']] = $filter['value'];
        }

        return $this->scopeFilter($builder, $mapped);
    }

    /**
     * Фильтрация данных
     *
     * Передается массив данных для фильтрации вида ключ=>значение
     *
     * Сперва ищется функция вида filterКлюч - вызывается
     *
     * Далее обрабатывается ключ:
     *
     * 1) Переводится из camelCase в snake_case
     *
     * 2) если ключ заканчивается на:
     *
     *    From (например priceFrom) применяется оператор >=
     *    To (например priceTo) применяется оператор <=
     *    Not (например oldPriceNot) применяется оператор <>
     *    Like (например titleLike) применяется оператор like %$value%
     *
     * 3) если значение === 'null' будут использованы операторы is null или not null
     *
     * Поля не выполняются если их нет в массиве fillable + ['id', 'created_at', 'updated_at']
     *
     * Если значение массив используется whereIn или whereNotIn
     *
     * Фильтр понимает отношения ManyToMany (например: categories=11)
     *
     * в отношениях можно использовать внешний ключ отличный от id, например: categories.slug
     * также работают все вышеперечисленные условия (From To и т.д.)
     *
     * @param $builder
     * @param array $filter
     * @return mixed
     *
     * @throws Exception
     * @example
     * filter => {
     *   id => 100,
     *   article => [1212, 3233, 4333]
     *   priceFrom => 1000,
     *   priceTo => 9000,
     *   ratingFrom => 3,
     *   quantityNot => 0,
     *   categories.slug => category-title,
     *   categories._lftFrom = 3,
     *   categories._rgtTo = 32,
     * }
     *
     **@todo Добавить возможность условия ИЛИ
     */
    public function scopeFilter($builder, array $filter = []): mixed
    {
        if (empty($filter)) {
            return $builder;
        }

        $allowed = array_merge($this->fillable, ['id', 'created_at', 'updated_at']);

        foreach ($filter as $fieldTitle => $value) {
            $method = Str::camel("filter_$fieldTitle");
            $field = Str::snake($fieldTitle);
            if (method_exists($this, $method)) {
                $this->$method($builder, $value);
            } else {
                $operand = '=';
                if (Str::endsWith($field, '_from')) {
                    $field = Str::substr($field, 0, -5);
                    $operand = '>=';
                } elseif (Str::endsWith($field, '_to')) {
                    $field = Str::substr($field, 0, -3);
                    $operand = '<=';
                } elseif (Str::endsWith($field, '_not')) {
                    $field = Str::substr($field, 0, -4);
                    $operand = '<>';
                } elseif (Str::endsWith($field, '_like')) {
                    $field = Str::substr($field, 0, -5);
                    $operand = 'LIKE';
                    $value = "%$value%";
                }

                // поле вида categories.slug - для связей
                $foreignKey = 'id';
                if (Str::contains($field, '.')) {
                    [$field, $foreignKey] = explode('.', $field);
                }

                // Связь через отношения
                if (method_exists($this, $field)) {
                    if ($this->$field() instanceof Relation) {
                        $builder->whereHas($field, function ($query) use ($field, $value, $foreignKey, $operand) {
                            $table = $this->$field()->getRelated()->getTable();
                            return $this->_applyWhere($query, "$table.$foreignKey", $value, $operand);
                        });
                    } else {
                        throw new Exception("Не является отношением для фильтрации: $field", "badFilter");
                    }
                } elseif (in_array($field, $allowed)) {// Обычные параметры
                    $this->_applyWhere($builder, $field, $value, $operand);
                } else {
                    throw new Exception("Не найдено свойство/отношение для фильтрации: $field", "badFilter");
                }
            }
        }

        return $builder;
    }

    public function _applyWhere($builder, $field, $value, $operand)
    {
        if (is_array($value)) {
            if ($operand === '<>') {
                $builder->whereNotIn($field, $value);
            } else {
                $builder->whereIn($field, $value);
            }
        } elseif ($value === 'null') {
            if ($operand === '<>') {
                $builder->whereNotNull($field);
            } else {
                $builder->where($field, null);
            }
        } else {
            $builder->where($field, $operand, $value);
        }

        return $builder;
    }

    /**
     * Специфичный фильтр по названию
     *
     * @param $builder
     * @param $value
     */
    public function filterName($builder, $value): void
    {
        $builder->where('name', 'LIKE', "%{$value}%")
            ->orderByRaw(
                "case
                when `name` LIKE '{$value}' then 1
                when `name` LIKE '{$value}%' then 2
                when `name` LIKE '%{$value}%' then 3
                else 4 end"
            );
    }
}
